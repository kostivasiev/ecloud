<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\InstallSoftware;
use App\Jobs\Instance\Deploy\RegisterLogicMonitorDevice;
use App\Models\V2\Task;
use App\Support\Sync;
use Database\Seeders\Images\CentosWithMcafeeSeeder;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterLogicMonitorDeviceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        //$this->createSyncUpdateTask($this->instanceModel());
    }

    public function testNoFloatingIpSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        //TODO assert no logic monitor calls

        dispatch(new RegisterLogicMonitorDevice($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeviceAlreadyRegisteredSkips()
    {


    }
}
