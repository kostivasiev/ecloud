<?php

namespace Tests\V1\VirtualMachines;

use App\Models\V1\VirtualMachine;
use Tests\V1\TestCase;

class CloneTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPublicCloneDisabled()
    {
        VirtualMachine::factory(1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Public',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(403);
    }

    public function testBurstCloneDisabled()
    {
        VirtualMachine::factory(1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Burst',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertStatus(403);
    }
}
