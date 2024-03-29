<?php
namespace Tests\V2\VpnEndpoint;

use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    use VpnSessionMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testDeleteResource()
    {
        Event::fake(Created::class);
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint()->id)
            ->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testDeleteResourceWrongUser()
    {
        $this->be(new Consumer(999, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Not found',
                    'detail' => 'No Vpn Endpoint with that ID was found',
                ]
            )->assertStatus(404);
    }

    public function testDeleteResourceWhenVpnSessionExists()
    {
        $this->vpnSession();
        Event::fake(Created::class);
        $this->delete('/v2/vpn-endpoints/' . $this->vpnEndpoint()->id)
            ->assertJsonFragment(
                [
                    'title' => 'Forbidden',
                    'detail' => 'Vpn Endpoints that have associated Vpn Sessions cannot be deleted',
                ]
            )->assertStatus(403);
    }
}