<?php

namespace Tests\V2\ResourceTiers;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\ResourceTier;
use Database\Seeders\ResourceTierSeeder;
use Tests\TestCase;

class GetTest extends TestCase
{
    public const RESOURCE_URI = '/v2/resource-tiers/%s';
    public const AZ_RESOURCE_URI = '/v2/availability-zones/%s/resource-tiers';
    public const RT_HOST_GROUPS_URI = '/v2/resource-tiers/%s/host-groups';

    private ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();
        $this->resourceTier = ResourceTier::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
        ])->create();
    }

    public function testGetResourceTier()
    {
        $this->asAdmin()
            ->get(sprintf($this::RESOURCE_URI, $this->resourceTier->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ])->assertStatus(200);
    }

    public function testNoneAdminnCantGetResourceTier()
    {
        $this->asUser()
            ->get(sprintf($this::RESOURCE_URI, $this->resourceTier->id))
            ->assertStatus(200);
    }

    public function testGetResourceTierFromAvailabilityZoneAsAdmin()
    {
        $this->asAdmin()
            ->get(sprintf($this::AZ_RESOURCE_URI,  $this->availabilityZone()->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ])->assertStatus(200);
    }

    public function testGetResourceTierFromAvailabilityZoneAsUser()
    {
        $this->asUser()
            ->get(sprintf($this::AZ_RESOURCE_URI, $this->availabilityZone()->id))
            ->assertStatus(200);
    }

    public function testGetHostGroupsForResourceTierAsAdminPasses()
    {
        AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();

        $this->asAdmin()
        ->get(sprintf($this::RT_HOST_GROUPS_URI, 'rt-aaaaaaaa'))
            ->assertJsonFragment([
                'id' => 'hg-standard-cpu',
                'name' => 'Standard CPU Host Group',
                'availability_zone_id' => 'az-aaaaaaaa',
                'host_spec_id' => 'hs-standard-cpu',
            ])
        ->assertStatus(200);
    }

    public function testGetHostGroupsForResourceTierAsUserFails()
    {
        $this->asUser()
            ->get(sprintf($this::RT_HOST_GROUPS_URI, $this->resourceTier->id))
            ->assertStatus(401);
    }
}
