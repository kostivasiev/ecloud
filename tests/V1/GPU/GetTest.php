<?php

namespace Tests\GPU;

use App\Models\V1\GpuProfile;
use App\Models\V1\VirtualMachine;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Laravel\Lumen\Testing\DatabaseTransactions;


class GetTest extends TestCase
{
    use DatabaseMigrations, DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->gpu_profile = factory(GpuProfile::class, 1)->create()->first();
    }

    public function testValidCollection()
    {
        $this->get('/v1/gpu-profiles', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);



        $this->json('GET', '/v1/gpu-profiles', [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
                'profile_name' => $this->gpu_profile->profile_name,
                'card_type' => $this->gpu_profile->card_type,

            ]);
    }

    public function testValidItem()
    {
        $this->get('/v1/gpu-profiles', [
            'X-consumer-custom-id' => '1-1',
            'X-consumer-groups' => 'ecloud.read',
        ]);

        $this->json('GET', '/v1/gpu-profiles/' . $this->gpu_profile->getKey(), [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
                'profile_name' => $this->gpu_profile->profile_name,
                'card_type' => $this->gpu_profile->card_type,
            ]);
    }
    
    public function testGetGpuResourcePoolAvailability()
    {
        config(['gpu.cards_available' => 5]);
        $this->assertEquals(5, GpuProfile::gpuResourcePoolAvailability());

        $vms = factory(VirtualMachine::class, 1)->create()->first();
        $vms->servers_ecloud_gpu_profile_uuid = $this->gpu_profile->getKey();
        $vms->save();

        $this->assertEquals(4, GpuProfile::gpuResourcePoolAvailability());
    }
}
