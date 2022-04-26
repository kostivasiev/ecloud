<?php
namespace Tests\V2\Console\Commands\LogicMonitor;

use App\Jobs\Router\CreateCollectorRules;
use App\Jobs\Router\CreateSystemPolicy;
use App\Models\V2\Credential;
use App\Models\V2\Image;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Illuminate\Console\Command;
use Queue;
use Tests\TestCase;

class RegisterExistingInstancesWithLogicMonitorTest extends TestCase
{
    public function testCommandDispatchesJobsForInstancesAndRoutersSuccess()
    {
        //prep
        $mockMonitoringAdminClient = \Mockery::mock(\UKFast\Admin\Monitoring\AdminClient::class);
        $mockMonitoringAdminAccountClient = \Mockery::mock(\UKFast\Admin\Monitoring\AdminAccountClient::class);
        $mockMonitoringAdminDeviceClient = \Mockery::mock(\UKFast\Admin\Monitoring\AdminDeviceClient::class);
        $mockMonitoringAdminCollectorClient = \Mockery::mock(\UKFast\Admin\Monitoring\AdminCollectorClient::class);
        $mockPageItems = \Mockery::mock(\UKFast\SDK\Page::class);

        $mockMonitoringAdminDeviceClient->shouldReceive('getAll')->andReturn(
            null
        );

        $mockMonitoringAdminClient->shouldReceive('devices')->andReturn(
            $mockMonitoringAdminDeviceClient
        );

        $mockMonitoringAdminClient->shouldReceive('setResellerId')->andReturn(
            $mockMonitoringAdminClient
        );

        $stdClass = new \stdClass;
        $stdClass->id = 1;

        $mockMonitoringAdminAccountClient->shouldReceive('getAll')->andReturn(
            [$stdClass]
        );

        $mockMonitoringAdminClient->shouldReceive('accounts')->andReturn(
            $mockMonitoringAdminAccountClient
        );


        $mockPageItems->shouldReceive('totalItems')->andReturn(
            1
        );

        $mockPageItems->shouldReceive('getItems')->andReturn(
            [$stdClass]
        );

        $mockMonitoringAdminCollectorClient->shouldReceive('getPage')->andReturn(
            $mockPageItems
        );

        $mockMonitoringAdminClient->shouldReceive('collectors')->andReturn(
            $mockMonitoringAdminCollectorClient
        );

        app()->bind(
            \UKFast\Admin\Monitoring\AdminClient::class,
            function () use ($mockMonitoringAdminClient) {
                return $mockMonitoringAdminClient;
            }
        );

        //make instance
        $image = Image::factory()->create([
            'platform' => 'windows'
        ]);
        $vpc = Vpc::factory()->create();
        $instance = Instance::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'image_id' => $image->id,
            'vpc_id' => $vpc->id
        ]);

        $this->kingpinServiceMock()
            ->shouldReceive('post')
            ->withArgs([
                '/api/v2/vpc/' . $instance->vpc->id . '/instance/' . $instance->id . '/guest/linux/user',
                [
                    'json' => [
                        "targetUsername" => "lm.".$instance->id,
                        "targetPassword" => "somepassword",
                        "targetSudo" => false,
                        "username" => "graphite.rack",
                        "password" => "somepassword",]
                    ]
            ])->andReturns(
                new Response(200)
            );

        Credential::factory()->create([
            'username' => 'graphite.rack',
            'resource_id' => $instance->id
        ]);

        Vpc::factory()->create(['id'=> 'vpc-a7d7c4e6']);
        Router::factory()->create(['id' => 'rtr-62827a58']);
        Network::factory()->create();

        /** @var Network $network */
        $network = Network::all()->first();
        NetworkPolicy::factory()->create(['network_id' => $network->id]);

        // Fake the queue
        Queue::fake();

        $this->artisan('lm:register-all-instances')
            ->assertExitCode(Command::SUCCESS);

        // Assert the job was pushed to the queue
        Queue::assertPushed(CreateSystemPolicy::class);
        Queue::assertPushed(CreateCollectorRules::class);
    }
}