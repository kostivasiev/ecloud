<?php

namespace Tests\VirtualMachines;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\VirtualMachine;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPublicCloneDisabled()
    {
        factory(VirtualMachine::class, 1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Public',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }

    public function testBurstCloneDisabled()
    {
        factory(VirtualMachine::class, 1)->create([
            'servers_id' => 123,
            'servers_ecloud_type' => 'Burst',
        ]);

        $this->json('POST', '/v1/vms/123/clone', [

        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }
}
