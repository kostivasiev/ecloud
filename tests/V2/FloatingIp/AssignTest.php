<?php

namespace Tests\V2\FloatingIp;

use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class AssignTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testNicAssignsNatsSuccess()
    {
        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])
            ->assertResponseStatus(202);

        // Check nats were created
        $this->assertTrue($this->floatingIp()->sourceNat()->exists());
        $this->assertTrue($this->floatingIp()->destinationNat()->exists());
    }

    public function testSuccess()
    {
        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])
            ->seeInDatabase('floating_ips', [
                'id' => $this->floatingIp()->id,
                'resource_id' => $this->nic()->id
            ], 'ecloud')
            ->assertResponseStatus(202);
    }

    public function testAlreadyAssigned()
    {
        $this->floatingIp()->resource()->associate($this->nic())->save();

        $this->post('/v2/floating-ips/' . $this->floatingIp()->id .'/assign', [
            'resource_id' => $this->nic()->id
        ])->assertResponseStatus(409);
    }
}
