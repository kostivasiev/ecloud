<?php

namespace Tests\V2\ResourceTiers;

use App\Models\V2\ResourceTier;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    public const RESOURCE_URI = '/v2/resource-tiers/%s';
    public ResourceTier $resourceTier;

    public function setUp(): void
    {
        parent::setUp();
        $this->resourceTier = ResourceTier::factory([
            'availability_zone_id' => $this->availabilityZone()->id,
            'name' => 'Old Name',
        ])->create();
    }

    public function testUpdateResourceTierAsAdmin()
    {
        $data = [
            'name' => 'New Name',
            'active' => false,
        ];

        $this->asAdmin()
            ->patch(sprintf(static::RESOURCE_URI,$this->resourceTier->id), $data)
            ->assertStatus(200);

        $this->assertDatabaseHas(
            'resource_tiers',
            $data,
            'ecloud'
        );
    }

    public function testUpdateResourceTierAsUser()
    {
        $data = [
            'name' => 'New Name',
        ];

        $this->asUser()
            ->patch(sprintf(static::RESOURCE_URI,$this->resourceTier->id), $data)
            ->assertStatus(401);

        $this->assertDatabaseMissing(
            'resource_tiers',
            $data,
            'ecloud'
        );
    }
}
