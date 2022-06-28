<?php

namespace Tests\V2\ResourceTierHostGroup;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\ResourceTier;
use Tests\TestCase;

class CreateTest extends TestCase
{
    public const COLLECTION_URI = '/v2/resource-tier-host-groups';

    protected AvailabilityZone $availabilityZone;
    protected ResourceTier $resourceTier;
    protected HostGroup $hostGroup;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        $this->resourceTier = ResourceTier::factory()->create([
            'name' => 'Standard CPU',
            'availability_zone_id' => $this->availabilityZone()->id
        ]);

        $this->hostGroup = HostGroup::factory()->create([
            'name' => 'Standard CPU Host Group',
            'availability_zone_id' => $this->availabilityZone()->id,
            'host_spec_id' => 'hs-standard-cpu',
        ]);
    }

    public function testCreateAdminPasses()
    {
        $data = [
            'resource_tier_id' => $this->resourceTier->id,
            'host_group_id' => $this->hostGroup->id
        ];

        $this->asAdmin()
            ->post(static::COLLECTION_URI, $data)
            ->assertStatus(201);

        $this->assertDatabaseHas(
            'resource_tier_host_group',
            $data,
            'ecloud'
        );
    }

    public function testCreateUserFails()
    {
        $data = [
            'resource_tier_id' => $this->resourceTier->id,
            'host_group_id' => $this->hostGroup->id
        ];

        $this->asUser()
            ->post(static::COLLECTION_URI, $data)
            ->assertStatus(401);
    }

    public function testNotSameAvailabilityZoneFails()
    {
        $this->hostGroup = HostGroup::factory()->create([
            'name' => 'Standard CPU Host Group',
            'availability_zone_id' => $this->availabilityZone->id,
            'host_spec_id' => 'hs-standard-cpu',
        ]);

        $data = [
            'resource_tier_id' => $this->resourceTier->id,
            'host_group_id' => $this->hostGroup->id
        ];

        $this->asAdmin()
            ->post(static::COLLECTION_URI, $data)
            ->assertStatus(422);
    }
}
