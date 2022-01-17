<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Region;
use App\Rules\V2\IsSameRegion;
use Tests\TestCase;

class IsSameRegionTest extends TestCase
{
    public function testAzAndVpcInSameRegion()
    {
        $rule = new IsSameRegion($this->vpc()->id);
        $this->assertTrue(
            $rule->passes(
                'availability_zone_id',
                $this->availabilityZone()->id
            )
        );
    }

    public function testAzAndVpcNotInSameRegion()
    {
        $region = factory(Region::class)->create([
            'id' => 'reg-alternate',
            'is_public' => true,
        ]);
        $this->vpc()->setAttribute('region_id', $region->id)->saveQuietly();

        $rule = new IsSameRegion($this->vpc()->id);
        $this->assertFalse(
            $rule->passes(
                'availability_zone_id',
                $this->availabilityZone()->id
            )
        );
    }
}
