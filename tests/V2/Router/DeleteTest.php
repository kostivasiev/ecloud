<?php

namespace Tests\V2\Router;

use App\Events\V2\Task\Created;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    private Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();

        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);

        $this->nsxServiceMock()
            ->allows('delete')
            ->andReturnUsing(function () {
                return new Response(204, [], '');
            });
        $this->nsxServiceMock()
            ->allows('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
    }

    public function testSuccessfulDelete()
    {
        $this->be($this->consumer);
        Event::fake(Created::class);
        $this->delete('/v2/routers/' . $this->router()->id)
            ->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testDeleteFailsIfChildPresent()
    {
        $this->be($this->consumer);
        $this->network();
        $this->delete('/v2/routers/' . $this->router()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->network()->id,
                ]
            )->assertStatus(412);
    }
}
