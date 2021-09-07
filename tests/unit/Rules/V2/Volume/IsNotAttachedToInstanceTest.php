<?php

namespace Tests\unit\Rules\V2\Volume;

use App\Models\V2\Volume;
use App\Rules\V2\Volume\IsNotAttachedToInstance;
use Tests\Mocks\Resources\VolumeGroupMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNotAttachedToInstanceTest extends TestCase
{
    use VolumeGroupMock;

    public function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    public function testVolumeNotAttachedPasses()
    {
        $volume = Volume::factory()
            ->sharedVolume($this->volumeGroup()->id)
            ->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
            ]);

        $rule = new IsNotAttachedToInstance($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertTrue($result);
    }

    public function testVolumeAttachedFails()
    {
        $volume = Volume::factory()->create([
            'vpc_id' => $this->vpc()->id,
        ]);

        $volume->instances()->attach($this->instance());

        $rule = new IsNotAttachedToInstance($volume->id);

        $result = $rule->passes('volume_group_id', $this->volumeGroup()->id);

        $this->assertFalse($result);
    }
}