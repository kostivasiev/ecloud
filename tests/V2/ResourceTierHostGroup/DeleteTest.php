<?php

namespace Tests\V2\ResourceTierHostGroup;

use App\Models\V2\AvailabilityZone;
use Database\Seeders\ResourceTierSeeder;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    protected AvailabilityZone $availabilityZone;

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

    public function testDeleteResourceAsUserFails()
    {
        $this->asUser()
            ->delete(sprintf(static::ITEM_URI, 'rthg-standard-cpu'))
            ->assertStatus(401);
    }

    public function testDeleteResourceAsAdminPasses()
    {
        $this->asAdmin()
            ->delete(sprintf(static::ITEM_URI, 'rthg-standard-cpu'))
            ->assertStatus(204);
    }
}
