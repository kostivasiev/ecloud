<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\AssignClusterIp;
use App\Models\V2\IpAddress;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AssignClusterIpTest extends TestCase
{
    use VipMock;

    public function testAssignIpAddress()
    {
        Event::fake([JobFailed::class]);

        dispatch(new AssignClusterIp($this->createSyncUpdateTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);

        $this->vip()->refresh();

        $this->assertNotNull($this->vip()->ipAddress);

        $this->assertEquals(IpAddress::TYPE_CLUSTER, $this->vip()->ipAddress->type);
    }

    public function testIpAddressAlreadyAssignedSkips()
    {
        Event::fake([JobFailed::class]);

        $ipAddress = $this->vip()->assignClusterIp();

        dispatch(new AssignClusterIp($this->createSyncUpdateTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);

        $this->vip()->refresh();

        $this->assertEquals($ipAddress->id, $this->vip()->ipAddress->id);
    }
}