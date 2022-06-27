<?php

namespace Tests\Unit\Models;


use App\Models\V2\AvailabilityZone;
use App\Models\V2\ResourceTier;
use Database\Seeders\ResourceTierSeeder;
use Tests\TestCase;

class ResourceTierHostGroupTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);

        (new ResourceTierSeeder())->run();
    }

    public function testRelationships()
    {
        $resourceTierStandardCpu = ResourceTier::find('rt-standard-cpu');

        $resourceTierHostGroup = $resourceTierStandardCpu->resourceTierHostGroups->first();

        // pivot -> host group
        $this->assertEquals('hg-standard-cpu', $resourceTierHostGroup->hostGroup->id);

        // pivot -> resource tier
        $this->assertEquals('rt-standard-cpu', $resourceTierHostGroup->resourceTier->id);

        // resource tier -> through pivot -> host groups
        $this->assertEquals('hg-standard-cpu', $resourceTierStandardCpu->hostGroups->first()->id);
    }
}
