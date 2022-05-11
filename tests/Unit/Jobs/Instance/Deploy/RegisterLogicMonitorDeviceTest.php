<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\RegisterLogicMonitorDevice;
use App\Models\V2\Credential;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\AdminDeviceClient;
use UKFast\Admin\Monitoring\Entities\Collector;
use UKFast\SDK\Page;
use UKFast\SDK\SelfResponse;

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

        dispatch(new RegisterLogicMonitorDevice(
            $this->createSyncUpdateTask($this->instanceModel())
        ));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeviceAlreadyRegisteredSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id,
        ])->saveQuietly();

        $mockMonitoringAdminDeviceClient = \Mockery::mock(AdminDeviceClient::class);
        $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn(
            [1]
        );
        $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class)->makePartial();
        $mockAdminMonitoringClient->allows('collectors->getPage')->andThrow(\Exception::class, 'This should not be called');
        $mockAdminMonitoringClient->shouldReceive('devices')->andReturn(
            $mockMonitoringAdminDeviceClient
        );
        app()->bind(AdminClient::class, function () use ($mockAdminMonitoringClient) {
            return $mockAdminMonitoringClient;
        });

        dispatch(new RegisterLogicMonitorDevice(
            $this->createSyncUpdateTask($this->instanceModel())
        ));

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
            $mockMonitoringAdminDeviceClient = \Mockery::mock(AdminDeviceClient::class);
            $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn([]);
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class)->makePartial();
            $mockAdminMonitoringClient->shouldReceive('devices')->andReturn(
                $mockMonitoringAdminDeviceClient
            );

            // Get collector ID (empty collection / no collector)
            $mockAdminMonitoringClient->expects('collectors->getPage')->andReturnUsing(function () {
                $page = \Mockery::mock(Page::class)->makePartial();
                $page->expects('totalItems')->andReturn(0);
                return $page;
            });
            // register device (should not be called)
            $mockAdminMonitoringClient->shouldNotReceive('devices->createEntity');
            return $mockAdminMonitoringClient;
        });

        dispatch(new RegisterLogicMonitorDevice(
            $this->createSyncUpdateTask($this->instanceModel(), [
                'logic_monitor_account_id' => 'some-account-id'
            ])
        ));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testNoLogicMonitorCredentialsFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockMonitoringAdminDeviceClient = \Mockery::mock(AdminDeviceClient::class);
            $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn([]);
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->shouldReceive('devices')->andReturn(
                $mockMonitoringAdminDeviceClient
            );
            // Get collector ID
            $mockAdminMonitoringClient->expects('collectors->getPage')->andReturnUsing(function () {
                $page = \Mockery::mock(Page::class)->makePartial();
                $page->expects('totalItems')->andReturn(2);
                $page->expects('getItems')->andReturnUsing(function () {
                    return [
                        new Collector([
                            'id' => 123
                        ]),
                        new Collector([
                            'id' => 456
                        ]),
                    ];
                });
                return $page;
            });
            // register device (should not be called)
            $mockAdminMonitoringClient->shouldNotReceive('devices->createEntity');

            return $mockAdminMonitoringClient;
        });

        $task = $this->createSyncUpdateTask($this->instanceModel(), [
            'logic_monitor_account_id' => 'some-account-id'
        ]);

        dispatch(new RegisterLogicMonitorDevice($task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id,
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockMonitoringAdminDeviceClient = \Mockery::mock(AdminDeviceClient::class);
            $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn([]);
            $mockMonitoringAdminDeviceClient->expects('createEntity')
                ->withAnyArgs()
                ->andReturnUsing(function () {
                    $mockSelfResponse =  \Mockery::mock(SelfResponse::class)->makePartial();
                    $mockSelfResponse->allows('getId')->andReturns('device-123');
                    return $mockSelfResponse;
                });
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->shouldReceive('devices')->andReturn(
                $mockMonitoringAdminDeviceClient
            );
            // Get collector ID
            $mockAdminMonitoringClient->expects('collectors->getPage')->andReturnUsing(function () {
                $page = \Mockery::mock(Page::class)->makePartial();
                $page->expects('totalItems')->andReturn(2);
                $page->expects('getItems')->andReturnUsing(function () {
                    return [
                        new Collector([
                            'id' => 123
                        ]),
                        new Collector([
                            'id' => 456
                        ]),
                    ];
                });
                return $page;
            });

            return $mockAdminMonitoringClient;
        });

        $task = $this->createSyncUpdateTask($this->instanceModel(), [
            'logic_monitor_account_id' => 'some-account-id'
        ]);

        $credential = app()->make(Credential::class);
        $credential->fill([
            'username' => 'lm.' . $this->instanceModel()->id,
        ]);
        $this->instanceModel()->credentials()->save($credential);

        dispatch(new RegisterLogicMonitorDevice($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
