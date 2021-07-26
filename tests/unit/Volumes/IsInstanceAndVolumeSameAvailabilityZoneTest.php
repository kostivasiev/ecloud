<?php

namespace Tests\unit\Instance;

use App\Models\V2\Volume;
use App\Rules\V2\IsInstanceAndVolumeSameAvailabilityZone;
use App\Rules\V2\IsVolumeAndInstanceSameAvailabilityZone;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsInstanceAndVolumeSameAvailabilityZoneTest extends TestCase
{
    protected IsInstanceAndVolumeSameAvailabilityZone $rule;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->volume = Volume::withoutEvents(function () {
            return factory(Volume::class)->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });
        $this->rule = new IsInstanceAndVolumeSameAvailabilityZone($this->volume->id);
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testInstanceAzMatches()
    {
        $this->assertTrue($this->rule->passes('instance_id', $this->instance()->id));
    }

    public function testVolumeAzDoesNotMatch()
    {
        $this->instance()->availability_zone_id = 'az-bbbbbbbb';
        $this->instance()->saveQuietly();
        $this->assertFalse($this->rule->passes('instance_id', $this->instance()->id));
    }
}