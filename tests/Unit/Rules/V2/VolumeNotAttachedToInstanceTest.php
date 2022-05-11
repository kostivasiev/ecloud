<?php

namespace Tests\Unit\Rules\V2;

use App\Models\V2\Volume;
use App\Rules\V2\VolumeNotAttachedToInstance;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class VolumeNotAttachedToInstanceTest extends TestCase
{
    public function testVolumeNotAttachedSucceeds()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));

        /** @var Volume $volume */
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $rule = new VolumeNotAttachedToInstance($this->instanceModel()->id);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertTrue($result);
    }

    public function testVolumeAttachedFails()
    {
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        /** @var Volume $volume */
        $volume = Volume::factory()->create([
            'id' => 'vol-test',
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);

        $this->instanceModel()->volumes()->attach($volume);

        $rule = new VolumeNotAttachedToInstance($this->instanceModel()->id);

        $result = $rule->passes('volume_id', $volume->id);

        $this->assertFalse($result);
    }
}