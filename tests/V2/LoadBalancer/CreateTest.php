<?php

namespace Tests\V2\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerSpecification;
use App\Models\V2\Network;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;
use UKFast\SDK\SelfResponse;

class CreateTest extends TestCase
{
    protected $faker;
    protected $region;
    protected $vpc;
    protected $lbs;
    protected $availabilityZone;

    private $lbConfigId = 123456;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->lbs = factory(LoadBalancerSpecification::class)->create();

        $this->vpc = Vpc::withoutEvents(function () {
            return factory(Vpc::class)->create([
                'id' => 'vpc-test',
                'name' => 'Manchester DC',
                'region_id' => $this->region->id
            ]);
        });

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id
        ]);

        $managementRouter = Model::withoutEvents(function () {
            return factory(Router::class)->create([
                'id' => 'rtr-mgmt',
                'vpc_id' => $this->vpc->id,
                'availability_zone_id' => $this->availabilityZone->id,
                'router_throughput_id' => $this->routerThroughput()->id,
                'is_management' => true,
            ]);
        });

        Model::withoutEvents(function () use ($managementRouter) {
            return factory(Network::class)->create([
                'id' => 'net-mgmt',
                'name' => 'Manchester Network',
                'subnet' => '10.0.0.0/24',
                'router_id' => $managementRouter->id
            ]);
        });

        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('createEntity')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        $mockSelfResponse = \Mockery::mock(SelfResponse::class)->makePartial();
                        $mockSelfResponse->allows('getId')->andReturns($this->lbConfigId);
                        return $mockSelfResponse;
                    });
                return $clusterMock;
            });
            return $mock;
        });
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name' => 'My Load Balancer',
            'load_balancer_spec_id' => $this->lbs->id,
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->availabilityZone->id
        ];

        $this->post(
            '/v2/load-balancers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIsFailed()
    {
        $data = [
            'name' => 'My Load Balancer',
            'load_balancer_spec_id' => $this->lbs->id,
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->post(
            '/v2/load-balancers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $data = [
            'name' => 'My Load Balancer',
            'load_balancer_spec_id' => $this->lbs->id,
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->post(
            '/v2/load-balancers',
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testFailedVpcCausesFail()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->vpc);
            $model->save();
        });

        $data = [
            'name' => 'My Load Balancer',
            'load_balancer_spec_id' => $this->lbs->id,
            'vpc_id' => $this->vpc->id,
            'availability_zone_id' => $this->availabilityZone->id
        ];
        $this->post(
            '/v2/load-balancers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson(
            [
                'title' => 'Validation Error',
                'detail' => 'The specified vpc id resource is currently in a failed state and cannot be used',
            ]
        )->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'My Load Balancer',
            'vpc_id' => $this->vpc->id,
            'load_balancer_spec_id' => $this->lbs->id,
            'availability_zone_id' => $this->availabilityZone->id
        ];
        $this->post(
            '/v2/load-balancers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(202);

        Event::assertDispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });
    }
}
