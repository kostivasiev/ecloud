<?php

namespace Tests\V2\VpnService;

use App\Events\V2\Task\Created;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DeleteTest extends TestCase
{
    protected $vpn;
    private Consumer $consumer;

    public function setUp(): void
    {
        parent::setUp();
        $this->consumer = new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']);
        $this->consumer->setIsAdmin(true);
        $this->vpn = VpnService::factory()->create([
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testSuccessfulDelete()
    {
        $this->be($this->consumer);
        Event::fake(Created::class);
        $this->delete('/v2/vpn-services/' . $this->vpn->id)
            ->assertStatus(202);
        Event::assertDispatched(Created::class);
    }

    public function testDeleteFailsIfChildPresent()
    {
        $this->be($this->consumer);
        $vpnEndpoint = VpnEndpoint::factory()->create([
            'name' => 'Create Test',
            'vpn_service_id' => $this->vpn->id,
        ]);
        $this->delete('/v2/vpn-services/' . $this->vpn->id)
            ->assertJsonFragment(
                [
                    'title' => 'Precondition Failed',
                    'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $vpnEndpoint->id,
                ]
            )->assertStatus(412);
    }
}
