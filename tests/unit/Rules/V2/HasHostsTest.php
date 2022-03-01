<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Rules\V2\HasHosts;
use Tests\TestCase;

class HasHostsTest extends TestCase
{
    protected $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new HasHosts();
    }

    public function testWithoutHostsFails()
    {
        $hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });
        $this->assertFalse($this->rule->passes('host_group_id', $hostGroup->id));
    }

    public function testNonExistentHostGroupFails()
    {
        $this->assertFalse($this->rule->passes('host_group_id', 'hg-123456'));
    }

    public function testWithHostsPasses()
    {
        $hostGroup = HostGroup::withoutEvents(function () {
            return factory(HostGroup::class)->create([
                'id' => 'hg-test',
                'name' => 'hg-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'host_spec_id' => $this->hostSpec()->id,
                'windows_enabled' => true,
            ]);
        });

        Host::withoutEvents(function () use ($hostGroup) {
            return factory(\App\Models\V2\Host::class)->create([
                'id' => 'h-test',
                'name' => 'h-test',
                'host_group_id' => $hostGroup->id,
            ]);
        });

        $this->assertTrue($this->rule->passes('host_group_id', $hostGroup->id));
    }
}