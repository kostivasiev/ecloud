<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLogicMonitorDevice;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Monitoring\AdminClient;

class CreateLogicMonitorAccountTest extends TestCase
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

    public function testPasses()
    {

        $notFoundException = \Mockery::mock(\UKFast\SDK\Exception\NotFoundException::class)->makePartial();
        $notFoundException->allows('getStatusCode')->andReturns(404);

        $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
        $mockAdminMonitoringClient->allows('setResellerId')->andReturns($mockAdminMonitoringClient);
        $mockAdminMonitoringClient->allows('accounts->getById')->andThrow($notFoundException);

        app()->bind(AdminClient::class, function () use ($mockAdminMonitoringClient) {
            return $mockAdminMonitoringClient;
        });


    }
}
