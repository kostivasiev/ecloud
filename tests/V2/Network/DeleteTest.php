<?php

namespace Tests\V2\Network;

use App\Events\V2\Task\Created;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->nsxServiceMock()
            ->allows('delete')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()
            ->allows('get')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['id' => 0]]]));
            });
    }

    public function testNoPermsIsDenied()
    {
        $this->delete(
            '/v2/networks/' . $this->network()->id,
            [],
            []
        )
            ->assertJsonFragment([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/networks/NOT_FOUND')
            ->assertJsonFragment([
                'title' => 'Not found',
                'detail' => 'No Network with that ID was found',
                'status' => 404,
            ])
            ->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Event::fake(Created::class);
        $this->delete('/v2/networks/' . $this->network()->id)
            ->assertStatus(202);
    }

    public function testDependentNicFailsDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Event::fake(Created::class);
        $this->nic();
        $this->delete('/v2/networks/' . $this->network()->id)->assertStatus(412);
        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }

    public function testDependentIpFailsDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Event::fake(Created::class);
        $this->ipAddress();
        $this->delete('/v2/networks/' . $this->network()->id)->assertStatus(412);
        Event::assertNotDispatched(\App\Events\V2\Task\Created::class);
    }
}
