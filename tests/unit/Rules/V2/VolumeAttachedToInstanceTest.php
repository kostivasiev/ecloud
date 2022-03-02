<?php

namespace Tests\unit\Rules\V2;

use App\Models\V2\Volume;
use App\Rules\V2\VolumeAttachedToInstance;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class VolumeAttachedToInstanceTest extends TestCase
{
    public function testVolumeAttachedSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $rule = new VolumeAttachedToInstance($this->instanceModel()->id);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertTrue($result);
    }

    public function testVolumeNotAttachedFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $rule = new VolumeAttachedToInstance($this->instanceModel()->id);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertFalse($result);
    }
}