<?php

namespace Tests\Unit\Rules\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\Volume\IsSharedVolume;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsSharedVolumeTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testVolumeIsSharedVolumePasses()
    {
        $volume = Volume::factory()
            ->sharedVolume($this->volumeGroup()->id)
            ->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
            ]);

        $rule = new IsSharedVolume($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }

    public function testVolumeIsNotSharedVolumeFails()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $rule = new IsSharedVolume($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }
}