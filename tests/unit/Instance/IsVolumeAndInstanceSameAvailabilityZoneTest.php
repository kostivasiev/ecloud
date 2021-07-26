<?php

namespace Tests\unit\Instance;

use App\Models\V2\Volume;
use App\Rules\V2\IsVolumeAndInstanceSameAvailabilityZone;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsVolumeAndInstanceSameAvailabilityZoneTest extends TestCase
{
    public IsVolumeAndInstanceSameAvailabilityZone $rule;

    public function setUp(): void
    {
        parent::setUp();
        $this->rule = new IsVolumeAndInstanceSameAvailabilityZone($this->instance()->id);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testVolumeAzMatches()
    {
        $volume = Volume::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });
        $this->assertTrue($this->rule->passes('volume_id', $volume->id));
    }

    public function testVolumeAzDoesNotMatch()
    {
        $volume = Volume::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => 'az-bbbbbbbb',
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });
        $this->assertFalse($this->rule->passes('volume_id', $volume->id));
    }
}