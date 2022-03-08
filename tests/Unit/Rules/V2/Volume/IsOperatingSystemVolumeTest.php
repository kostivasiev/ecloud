<?php

namespace Tests\Unit\Rules\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\Volume\IsOperatingSystemVolume;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsOperatingSystemVolumeTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testVolumeIsOperatingSystemVolumeFails()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'os_volume' => true
        ]);

        $rule = new IsOperatingSystemVolume($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }

    public function testVolumeIsNotOperatingSystemVolumePasses()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'os_volume' => false
        ]);

        $rule = new IsOperatingSystemVolume($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }
}