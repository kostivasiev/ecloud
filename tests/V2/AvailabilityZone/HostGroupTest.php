<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use Database\Seeders\ResourceTierSeeder;
use Database\Seeders\VpcSeeder;
use Tests\TestCase;

class HostGroupTest extends TestCase
{
    public HostGroup $hostGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone = AvailabilityZone::factory()->create([
            'id' => 'az-aaaaaaaa',
            'region_id' => $this->region()->id,
        ]);
        (new VpcSeeder())->run();
        (new ResourceTierSeeder())->run();
        $this->hostGroup = HostGroup::find('hg-standard-cpu');
    }

    public function testGetAvailableHostGroups()
    {
        $availableHostGroups = $this->availabilityZone->hostGroups()->toArray();
        $this->assertContains('hg-standard-cpu', $availableHostGroups[0]);
    }

    public function testGetDefaultHostGroup()
    {
        $this->assertEquals($this->hostGroup->id, $this->availabilityZone->getDefaultHostGroup()->id);
    }
}