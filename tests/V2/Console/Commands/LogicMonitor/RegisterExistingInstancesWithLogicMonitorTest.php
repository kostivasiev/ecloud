<?php
namespace Tests\V2\Console\Commands\LogicMonitor;

use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Credential;
use App\Models\V2\NetworkPolicy;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Queue;
use Tests\TestCase;

class RegisterExistingInstancesWithLogicMonitorTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->networkPolicy();
    }

    public function testCommandDispatchesJobsForFirewallAndNetworkPolicies()
    {
        Queue::fake();

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

        // Assert the job was pushed to the queue
        Queue::assertPushed(CreateSystemPolicy::class);
        Queue::assertPushed(AllowLogicMonitor::class);
        Queue::assertPushed(CreateCollectorRules::class);
    }

    public function testCommandRegistersInstancesSuccess()
    {
        // Create an instance to test with
        $this->credential = $this->instanceModel()->credentials()->save(
            Credential::factory()->create([
                'username' => config('instance.guest_admin_username.linux'),
            ])
        );

        $mockMonitoringAdminClient = \Mockery::mock(\UKFast\Admin\Monitoring\AdminClient::class);
        $mockMonitoringAdminClient->shouldReceive('setResellerId')->andReturnSelf();

        // Return null from devices API (device is not registered)
        $mockMonitoringAdminClient->shouldReceive('devices->getAll')->andReturnNull();

        $mockPageItems = \Mockery::mock(\UKFast\SDK\Page::class);

        $stdClass = new \stdClass;
        $stdClass->id = 1;

        $mockMonitoringAdminClient->shouldReceive('accounts->getAll')->andReturnNull();

        // Get customer details from accounts API
        app()->bind(
            \UKFast\Admin\Account\AdminClient::class,
            function () {
                $mockAccountAdminClient = \Mockery::mock(\UKFast\Admin\Account\AdminClient::class);
                $mockAccountAdminClient->shouldReceive('customers->getById')->andReturnUsing(function () {
                    return new class {
                        public $name = 'ABC Limited';
                    };
                });
                $mockAccountAdminClient->shouldReceive('accounts->createEntity')->andReturnUsing(function () {
                    return new class {
                        public $name = 'ABC Limited';
                    };
                });

                return $mockAccountAdminClient;
            }
        );

        $mockPageItems->shouldReceive('totalItems')->andReturn(1);
        $mockPageItems->shouldReceive('getItems')->andReturn(
            [$stdClass]
        );

        $mockMonitoringAdminClient->shouldReceive('collectors->getPage')->andReturn(
            $mockPageItems
        );

        app()->bind(
            \UKFast\Admin\Monitoring\AdminClient::class,
            function () use ($mockMonitoringAdminClient) {
                return $mockMonitoringAdminClient;
            }
        );

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

        // Fake the queue
        Queue::fake();

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

        // Assert the job was pushed to the queue
        Queue::assertPushed(CreateSystemPolicy::class);
        Queue::assertPushed(CreateCollectorRules::class);
    }
}