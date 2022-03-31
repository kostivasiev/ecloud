<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\CreateLogicMonitorAccount;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\Entities\Account;
use UKFast\SDK\SelfResponse;

class CreateLogicMonitorAccountTest extends TestCase
{
    public function testNoFloatingIpSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->shouldNotReceive('setResellerId');
            return $mockAdminMonitoringClient;
        });

        dispatch(new CreateLogicMonitorAccount(
            $this->createSyncUpdateTask($this->instanceModel())
        ));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAccountAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->expects('setResellerId')->andReturnSelf();
            $mockAdminMonitoringClient->expects('accounts->getAll')->andReturn([
                new Account([
                    'id' => 123
                ])
            ]);
            return $mockAdminMonitoringClient;
        });

        dispatch(new CreateLogicMonitorAccount(
            $this->createSyncUpdateTask($this->instanceModel())
        ));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testCanNotLoadAccountDetailsFails()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->expects('setResellerId')->andReturnSelf();
            $mockAdminMonitoringClient->expects('accounts->getAll')->andReturn([]);
            return $mockAdminMonitoringClient;
        });

        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () {
            $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
            $mockAccountAdminClient
                ->expects('customers->getById')
                ->andThrow(\Mockery::mock(\UKFast\SDK\Exception\NotFoundException::class));
            return $mockAccountAdminClient;
        });

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        dispatch(new CreateLogicMonitorAccount(
            $this->createSyncUpdateTask($this->instanceModel())
        ));

        Event::assertDispatched(JobFailed::class);
    }

    public function testPasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        // Admin Monitoring Client
        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->expects('setResellerId')->andReturnSelf();
            $mockAdminMonitoringClient->expects('accounts->getAll')->andReturn([]);
            $mockAdminMonitoringClient->expects('accounts->createEntity')
                ->withAnyArgs()
                ->andReturnUsing(function () {
                    $mockSelfResponse =  \Mockery::mock(SelfResponse::class)->makePartial();
                    $mockSelfResponse->allows('getId')->andReturns(123);
                    return $mockSelfResponse;
                });
            return $mockAdminMonitoringClient;
        });

        // Admin Account Client
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () {
            $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
            $mockAccountAdminClient->expects('customers->getById')->andReturn(
                new \UKFast\Admin\Account\Entities\Customer(
                    [
                        'name' => 'Paul\s Pies'
                    ]
                )
            );
            return $mockAccountAdminClient;
        });

        $this->instanceModel()->setAttribute('deploy_data', [
            'floating_ip_id' => $this->floatingIp()->id
        ])->saveQuietly();

        $task = $this->createSyncUpdateTask($this->instanceModel());

        dispatch(new CreateLogicMonitorAccount($task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $task->refresh();

        $this->assertEquals(123, $task->resource->deploy_data['logic_monitor_account_id']);
    }
}
