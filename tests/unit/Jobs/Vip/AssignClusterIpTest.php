<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\AssignClusterIp;
use App\Models\V2\IpAddress;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AssignClusterIpTest extends TestCase
{
    use VipMock;

    public function testAssignIpAddress()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AssignClusterIp($this->createSyncUpdateTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->vip()->refresh();

        $this->assertNotNull($this->vip()->ipAddress);

        $this->assertEquals(IpAddress::TYPE_CLUSTER, $this->vip()->ipAddress->type);
    }

    public function testIpAddressAlreadyAssignedSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $ipAddress = $this->vip()->assignClusterIp();

        dispatch(new AssignClusterIp($this->createSyncUpdateTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);

        $this->vip()->refresh();

        $this->assertEquals($ipAddress->id, $this->vip()->ipAddress->id);
    }
}