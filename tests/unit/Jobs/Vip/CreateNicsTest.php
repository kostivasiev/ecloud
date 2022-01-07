<?php

namespace Tests\unit\Jobs\Vip;

use App\Jobs\Vip\CreateNics;
use App\Models\V2\IpAddress;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class CreateNicsTest extends TestCase
{
    use VipMock;

    public function testNicDoesNotExistCreates()
    {
        Event::fake([JobFailed::class]);

        $this->loadBalancerInstance();

        dispatch(new CreateNics($this->createSyncUpdateTask($this->vip())));

        Event::assertNotDispatched(JobFailed::class);
    }

//    public function testNicAlreadyExistsSkips()
//    {
//        Event::fake([JobFailed::class]);
//
//        $ipAddress = $this->vip()->assignClusterIp();
//
//        dispatch(new CreateNics($this->createSyncUpdateTask($this->vip())));
//
//        Event::assertNotDispatched(JobFailed::class);
//
//        $this->vip()->refresh();
//
//        $this->assertEquals($ipAddress->id, $this->vip()->ipAddress->id);
//    }
}