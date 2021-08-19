<?php
namespace Tests\V2\VpnEndpoint;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use VpnEndpointMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testDeleteResource()
    {
        Event::fake(Created::class);
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint()->id)
            ->assertResponseStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testDeleteResourceWrongUser()
    {
        $this->be(new Consumer(999, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint()->id)
            ->seeJson(
                [
                    'title' => 'Not found',
                    'detail' => 'No Vpn Endpoint with that ID was found',
                ]
            )->assertResponseStatus(404);
    }
}