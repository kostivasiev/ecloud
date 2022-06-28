<?php

namespace Tests\V2\ResourceTiers;

use App\Models\V2\ResourceTier;
use Tests\TestCase;

class GetTest extends TestCase
{
    public const RESOURCE_TIER_COLLECTION_URI = '/v2/resource-tiers';
    public const RESOURCE_TIER_ITEM_URI = '/v2/resource-tiers/%s';
    public const AZ_RESOURCE_TIER_URI = '/v2/availability-zones/%s/resource-tiers';

    private ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();
        $this->resourceTier = ResourceTier::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
        ])->create();
    }

    public function testCollectionAsAdmin()
    {
        $this->asAdmin()
            ->get($this::RESOURCE_TIER_COLLECTION_URI)
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'active' => true,
            ])->assertStatus(200);
    }

    public function testCollectionAsAdminReturnsInactiveTiers()
    {
        $this->resourceTier->setAttribute('active', false)->save();

        $this->asAdmin()
            ->get($this::RESOURCE_TIER_COLLECTION_URI)
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'active' => false,
            ])->assertStatus(200);
    }

    public function testGetItemAsAdmn()
    {
        $this->asAdmin()
            ->get(sprintf($this::RESOURCE_TIER_ITEM_URI,  $this->resourceTier->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'active' => true,
            ])->assertStatus(200);
    }

    public function testGetItemAsAdmnReturnsInactiveTiers()
    {
        $this->resourceTier->setAttribute('active', false)->save();

        $this->asAdmin()
            ->get(sprintf($this::RESOURCE_TIER_ITEM_URI,  $this->resourceTier->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'active' => false,
            ])->assertStatus(200);
    }

    public function testGetItemAsUserDoesNotReturnsInactiveTiers()
    {
        $this->resourceTier->setAttribute('active', false)->save();

        $this->asUser()
            ->get(sprintf($this::RESOURCE_TIER_ITEM_URI,  $this->resourceTier->id))
            ->assertStatus(404);
    }

    public function testGetCollectionAsUserDoesNotReturnsInactiveTiers()
    {
        $this->resourceTier->setAttribute('active', false)->save();

        $this->asUser()
            ->get($this::RESOURCE_TIER_COLLECTION_URI)
            ->assertJsonMissing([
                'id' => $this->resourceTier->id,
            ])
            ->assertStatus(200);
    }

    public function testGetResourceTierFromAvailabilityZoneAsAdmin()
    {
        $this->asAdmin()
            ->get(sprintf($this::AZ_RESOURCE_TIER_URI,  $this->availabilityZone()->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'active' => true,
            ])->assertStatus(200);
    }

    public function testGetResourceTierFromAvailabilityZoneAsUser()
    {
        $this->asUser()
            ->get(sprintf($this::AZ_RESOURCE_TIER_URI,  $this->availabilityZone()->id))
            ->assertJsonFragment([
                'id' => $this->resourceTier->id,
                'name' => $this->resourceTier->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ])
            ->assertJsonMissing([
                'active' => true
            ])
            ->assertStatus(200);
    }
}
