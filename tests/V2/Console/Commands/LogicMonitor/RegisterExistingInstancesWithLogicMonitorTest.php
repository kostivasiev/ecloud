<?php
namespace Tests\V2\Console\Commands\LogicMonitor;

use App\Models\V2\Credential;
use App\Models\V2\IpAddress;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Monitoring\AdminClient;
use UKFast\Admin\Monitoring\AdminDeviceClient;
use UKFast\Admin\Monitoring\Entities\Collector;
use UKFast\SDK\Page;
use UKFast\SDK\SelfResponse;

class RegisterExistingInstancesWithLogicMonitorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy();
    }

    public function testCommandDispatchesJobsForFirewallAndNetworkPolicies()
    {
        $this->markTestSkipped();
        Event::fake([\App\Events\V2\Task\Created::class]);

        // Admin Account Client
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () {
            $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
            $mockAccountAdminClient->shouldNotReceive('customers->getById');
            return $mockAccountAdminClient;
        });

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }

    public function testCommandRegistersInstancesLogicMonitorSuccess()
    {
        // Create an instance to test with
        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.linux'),
            ])
        );

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

            $mockMonitoringAdminDeviceClient = \Mockery::mock(AdminDeviceClient::class);
            $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn([]);
            $mockMonitoringAdminDeviceClient->expects('createEntity')
                ->withAnyArgs()
                ->andReturnUsing(function () {
                    $mockSelfResponse =  \Mockery::mock(SelfResponse::class)->makePartial();
                    $mockSelfResponse->allows('getId')->andReturns('device-123');
                    return $mockSelfResponse;
                });

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

        // Assign a fIP to the instance
        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());
        $this->floatingIp()->resource()->associate($ipAddress);
        $this->floatingIp()->save();

        // Create Logic Monitor credentials
        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withArgs([
                '/api/v2/vpc/' . $this->instanceModel()->vpc->id . '/instance/' . $this->instanceModel()->id . '/guest/linux/user',
                [
                    'json' => [
                        'targetUsername' => 'lm.' . $this->instanceModel()->id,
                        'targetPassword' => 'somepassword',
                        'targetSudo' => false,
                        'username' => 'root',
                        'password' => 'somepassword'
                    ]
                ]
            ])
            ->andReturns(
                new Response(200)
            );

        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

//        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
//            return $event->model->name == 'sync_update';
//        });
    }

    public function testNoFloatingIpSkips()
    {
        // Create an instance to test with
        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.linux'),
            ])
        );

        // Admin Monitoring Client
        app()->bind(AdminClient::class, function () {
            $mockAdminMonitoringClient = \Mockery::mock(AdminClient::class);
            $mockAdminMonitoringClient->shouldNotReceive('devices->getAll'); // NOT!
            return $mockAdminMonitoringClient;
        });

        // Admin Account Client
        app()->bind(\UKFast\Admin\Account\AdminClient::class, function () {
            $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
            $mockAccountAdminClient->shouldNotReceive('customers->getById');
            return $mockAccountAdminClient;
        });

        Event::fake([\App\Events\V2\Task\Created::class]);

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

//        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
//            return $event->model->name == 'sync_update';
//        });
    }
}