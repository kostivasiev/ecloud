<?php

namespace Tests\Unit\Rules\V2\Instance;

use App\Rules\V2\Instance\DetachedIsNotShared;
use Tests\Mocks\Resources\VolumeMock;
use Tests\TestCase;
use UKFast\Api\Auth\Consumer;

class DetachedIsNotSharedTest extends TestCase
{
    use VolumeMock;

    protected DetachedIsNotShared $job;

    protected function setUp(): void
    {
        parent::setUp();
        $this->be(new Consumer(1, [config('app.name') . '.read', config('app.name') . '.write']));
        $this->job = new DetachedIsNotShared();
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
