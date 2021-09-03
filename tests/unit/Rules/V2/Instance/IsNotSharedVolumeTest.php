<?php

namespace Tests\Rules\V2\Instance;

use App\Rules\V2\Instance\IsNotSharedVolume;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class IsNotSharedVolumeTest extends TestCase
{
    use VolumeMock;

    protected IsNotSharedVolume $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->job = new IsNotSharedVolume('attach');
    }

    /** @test */
    public function nonSharedVolumePasses()
    {
        $this->assertTrue($this->job->passes('volume_id', $this->volume()->id));
    }

    /** @test */
    public function sharedVolumeFails()
    {
        $this->volume()->is_shared = true;
        $this->volume()->saveQuietly();
        $this->assertFalse($this->job->passes('volume_id', $this->volume()->id));
    }
}
