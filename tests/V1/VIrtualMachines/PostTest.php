<?php

namespace Tests\VirtualMachines;

use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

use App\Models\V1\VirtualMachine;
//use App\Models\V1\Solution;

class PostTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testPublicCreateDisabled()
    {
        $this->json('POST', '/v1/vms/', [
            'environment' => 'Public',
        ], [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.write',
        ]);

        $this->assertResponseStatus(403);
    }

//    todo need to mock kingpin/intapi services
//    public function testHybridCreate()
//    {
//        $this->missingFromDatabase('servers', [
//            'servers_id' => 123,
//        ]);
//
//        factory(Solution::class, 1)->create([
//            'ucs_reseller_id' => 12345,
//        ]);
//
//        $this->json('POST', '/v1/vms/', [
//            'environment' => 'Hybrid',
//            'template' => 'CentOS 7 64-bit',
//            'solution_id' => '12345',
//            'cpu' => 1,
//            'ram' => 2,
//            'hdd' => 30,
//        ], [
//            'X-consumer-custom-id' => '1-1',
//            'X-consumer-groups' => 'ecloud.write',
//        ]);
//
//        dd($this->response->getContent());
//
//        $this->assertResponseStatus(403);
//    }

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
