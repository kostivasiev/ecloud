<?php

namespace Tests\Unit\Rules\V2\Instance;

use App\Rules\V2\Instance\IsInstanceInVolumeGroup;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsInstanceInVolumeGroupTest extends TestCase
{
    use VolumeGroupMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    /** @test */
    public function failWhenInstanceHasVolumeGroup()
    {
        $this->instanceModel()->volume_group_id = $this->volumeGroup()->id;
        $this->instanceModel()->saveQuietly();

        $rule = new IsInstanceInVolumeGroup($this->instanceModel()->id);
        $this->assertFalse($rule->passes('volume_group_id', $this->volumeGroup()->id));
    }

    /** @test */
    public function passWhenInstanceHasNoVolumeGroup()
    {
        $rule = new IsInstanceInVolumeGroup($this->instanceModel()->id);
        $this->assertTrue($rule->passes('volume_group_id', $this->volumeGroup()->id));
    }
}