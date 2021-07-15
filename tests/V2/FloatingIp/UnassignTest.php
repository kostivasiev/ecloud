<?php

namespace Tests\V2\FloatingIp;

use App\Models\V2\VpnEndpoint;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class UnassignTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testNicUnAssignsDeletesNats()
    {
        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ]);
        $this->assertTrue($this->floatingIp()->sourceNat()->exists());
        $this->assertTrue($this->floatingIp()->destinationNat()->exists());

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign')
            ->assertResponseStatus(202);

        // Check nats were created
        $this->assertFalse($this->floatingIp()->sourceNat()->exists());
        $this->assertFalse($this->floatingIp()->destinationNat()->exists());
    }

    public function testSuccess()
    {
        $this->floatingIp()->resource()->associate($this->nic())->save();

        $this->seeInDatabase('floating_ips', [
            'id' => $this->floatingIp()->id,
            'resource_id' => $this->nic()->id
        ], 'ecloud');

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign')
            ->notSeeInDatabase('floating_ips', [
                'resource_id' => $this->nic()->id
            ], 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testVpnEndpointFloatingIpCanNotBeUnassigned()
    {
        $vpnEndpoint = factory(VpnEndpoint::class)->create();

        $this->floatingIp()->resource()->associate($vpnEndpoint)->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/unassign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertResponseStatus(403);
    }
}
