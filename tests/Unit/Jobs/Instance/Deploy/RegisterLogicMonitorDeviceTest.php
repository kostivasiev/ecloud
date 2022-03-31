<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLogicMonitorDevice;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Devices\Entities\Device;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\Entities\Collector;
use UKFast\SDK\Page;

class RegisterLogicMonitorDeviceTest extends TestCase
{
    public function testNoFloatingIpSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->shouldNotReceive('devices->getAll');
            return $mockAdminMonitoringClient;
        });

        dispatch(new RegisterLogicMonitorDevice($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeviceAlreadyRegisteredSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->expects('devices->getAll')->andReturnUsing(function () {
                return [
                    new Device()
                ];
            });
            return $mockAdminMonitoringClient;
        });


        // todo: assert ml call does not happen


        dispatch(new RegisterLogicMonitorDevice($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testNoCollectorForAvailabilityZoneSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            // Device does not already exist
            $mockAdminMonitoringClient->expects('devices->getAll')->andReturn([]);
            // Get collector ID (empty collection / no collector)
            $mockAdminMonitoringClient->expects('collectors->getPage')->andReturnUsing(function () {
                $page = \Mockery::mock(Page::class)->makePartial();
            });
            return $mockAdminMonitoringClient;
        });


        // todo: assert ml call does not happen


        dispatch(new RegisterLogicMonitorDevice($this->createSyncUpdateTask($this->instanceModel())));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });


    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            // Device does not already exist
            $mockAdminMonitoringClient->expects('devices->getAll')->andReturn([]);
            // Get collector ID
            $mockAdminMonitoringClient->expects('collectors->getPage')->andReturn(
                new Collection([
                    new Collector([
                        'id' => 1
                    ]),
                    new Collector([
                        'id' => 2
                    ])
                ])
            );


            return $mockAdminMonitoringClient;
        });





        dispatch(new RegisterLogicMonitorDevice($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });


    }
}
