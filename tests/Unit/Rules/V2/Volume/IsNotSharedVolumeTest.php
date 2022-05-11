<?php

namespace Tests\Unit\Rules\V2\Volume;

use App\Rules\V2\Volume\IsNotSharedVolume;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNotSharedVolumeTest extends TestCase
{
    use VolumeMock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
    }

    /** @test */
    public function nonSharedVolumePasses()
    {
        $job = new IsNotSharedVolume($this->volume()->id);
        $this->assertTrue($job->passes('instance_id', $this->instanceModel()->id));
    }

    /** @test */
    public function sharedVolumeFails()
    {
        $this->volume()->is_shared = true;
        $this->volume()->saveQuietly();
        $job = new IsNotSharedVolume($this->volume()->id);
        $this->assertFalse($job->passes('instance_id', $this->instanceModel()->id));
    }
}
