<?php

namespace Tests\unit\Rules\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\Volume\IsMemberOfVolumeGroup;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsMemberOfVolumeGroupTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testVolumeIsAlreadyMemberOfVolumeGroupFails()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'volume_group_id' => $this->volumeGroup()->id
        ]);

        $rule = new IsMemberOfVolumeGroup($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }

    public function testVolumeIsNotAlreadyMemberOfVolumeGroupPasses()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $rule = new IsMemberOfVolumeGroup($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }
}