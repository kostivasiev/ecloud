<?php

namespace Tests\V1\GPU;

use App\Models\V1\GpuProfile;
use App\Models\V1\VirtualMachine;
use Laravel\Lumen\Testing\DatabaseTransactions;
use Tests\V1\TestCase;


class GetTest extends TestCase
{
    use DatabaseTransactions;

    public function setUp(): void
    {
        parent::setUp();
        $this->gpu_profile = GpuProfile::factory()->create()->first();
    }

    public function testValidCollection()
    {
        $this->json('GET', '/v1/gpu-profiles', [], $this->validWriteHeaders)
            ->assertStatus(200)
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
                'profile_name' => $this->gpu_profile->profile_name,
                'card_type' => $this->gpu_profile->card_type,
            ]);
    }

    public function testValidCollectionReadOnly()
    {
        $this->json('GET', '/v1/gpu-profiles', [], $this->validReadHeaders)
            ->assertStatus(200)
            ->dontSeeJson([
                'profile_name' => $this->gpu_profile->profile_name,
            ])
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
                'card_type' => $this->gpu_profile->card_type,
            ]);
    }

    public function testValidItem()
    {
        $this->json('GET', '/v1/gpu-profiles/' . $this->gpu_profile->getKey(), [], $this->validWriteHeaders)
            ->seeStatusCode(200)
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
                'profile_name' => $this->gpu_profile->profile_name,
                'card_type' => $this->gpu_profile->card_type,
            ]);
    }

    public function testValidItemReadOnly()
    {
        $this->json('GET', '/v1/gpu-profiles/' . $this->gpu_profile->getKey(), [], $this->validReadHeaders)
            ->seeStatusCode(200)
            ->dontSeeJson([
                'profile_name' => $this->gpu_profile->profile_name,
            ])
            ->seeJson([
                'id' => $this->gpu_profile->getKey(),
                'name' => $this->gpu_profile->name,
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
