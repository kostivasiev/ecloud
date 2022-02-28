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
            ->seeJson([
                'title' => 'Unauthorized',
                'detail' => 'Unauthorized',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/networks/NOT_FOUND')
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Network with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Event::fake(Created::class);
        $this->delete('/v2/networks/' . $this->network()->id)
            ->assertResponseStatus(202);
    }

    public function testDependentNicFailsDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        Event::fake(Created::class);
        $this->nic();
        $this->delete('/v2/networks/' . $this->network()->id)->assertResponseStatus(412);
        $this->network()->refresh();
        $this->assertFalse($this->network()->trashed());
    }

    public function testDependentIpFailsDelete()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->ip();
        $this->delete('/v2/networks/' . $this->network()->id)->assertResponseStatus(412);
        $this->assertFalse($this->network()->trashed());
    }
}
