<?php

namespace Tests\Unit\Jobs\Vip;

use App\Jobs\Vip\UnassignClusterIp;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class UnassignClusterIpTest extends TestCase
{
    use VipMock;

    public function testUnassignIpAddress()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $ipAddress = $this->vip()->assignClusterIp();

        $this->assertTrue($this->vip()->ipAddress()->exists());

        dispatch(new UnassignClusterIp($this->createSyncDeleteTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->vip()->refresh();

        $this->assertNull($this->vip()->ipAddress);

        $this->assertNotNull($ipAddress->refresh()->deleted_at);
    }

    public function testNoClusterIpAssignedSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $task = $this->createSyncDeleteTask($this->vip());

        dispatch(new UnassignClusterIp($task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->assertNull($this->vip()->ipAddress);
    }
}