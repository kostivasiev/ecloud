<?php

namespace Tests\V2\ResourceTierHostGroup;

use App\Models\V2\AvailabilityZone;
use Database\Seeders\ResourceTierSeeder;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected AvailabilityZone $availabilityZone;

    public const COLLECTION_URI = '/v2/resource-tier-host-groups';
    public const ITEM_URI = '/v2/resource-tier-host-groups/%s';

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();
    }

    public function testGetCollectionAdminPasses()
    {
        $this->asAdmin()
            ->get(static::COLLECTION_URI)
            ->assertJsonFragment([
                'id' => 'rthg-standard-cpu',
                'resource_tier_id' => 'rt-aaaaaaaa',
                'host_group_id' => 'hg-99f9b758'
            ])->assertStatus(200);
    }

    public function testGetCollectionUserFails()
    {
        $this->asUser()
            ->get(static::COLLECTION_URI)
            ->assertStatus(401);
    }

    public function testGetItemAdminPasses()
    {
        $this->asAdmin()
            ->get(sprintf(static::ITEM_URI, 'rthg-standard-cpu'))
            ->assertJsonFragment([
                'id' => 'rthg-standard-cpu',
                'resource_tier_id' => 'rt-aaaaaaaa',
                'host_group_id' => 'hg-99f9b758'
            ])->assertStatus(200);
    }

    public function testGetItemUserFails()
    {
        $this->asUser()
            ->get(sprintf(static::ITEM_URI, 'rthg-standard-cpu'))
            ->assertStatus(401);
    }
}
