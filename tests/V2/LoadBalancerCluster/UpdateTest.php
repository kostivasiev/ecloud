<?php

namespace Tests\V2\LoadBalancerCluster;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Region;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use App\Support\Sync;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $faker;
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $lbc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->lbc = factory(LoadBalancerCluster::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name' => 'My Load Balancer Cluster',
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->availabilityZone()->id
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->id,
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

    public function testInvalidavailabilityZoneIsFailed()
    {
        $data = [
            'name' => 'My Load Balancer Cluster',
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->id,
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
            'name' => 'My Load Balancer Cluster',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->id,
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

    public function testFailedVpcCausesFailure()
    {
        // Force failure
        Model::withoutEvents(function () {
            $model = new Task([
                'id' => 'sync-test',
                'failure_reason' => 'Unit Test Failure',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $model->resource()->associate($this->vpc());
            $model->save();
        });

        $data = [
            'name' => 'My Load Balancer Cluster',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ];
        $this->patch(
            '/v2/lbcs/' . $this->lbc->id,
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
        $data = [
            'name' => 'My Load Balancer Cluster',
            'vpc_id' => $this->vpc()->id,
            'availability_zone_id' => $this->availabilityZone()->id
        ];
        $this->patch(
            '/v2/lbcs/' . $this->lbc->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $resourceId = (json_decode($this->response->getContent()))->data->id;
        $resource = LoadBalancerCluster::find($resourceId);


        $this->assertEquals($data['name'], $resource->name);
        $this->assertEquals($data['vpc_id'], $resource->vpc_id);
        $this->assertEquals($data['availability_zone_id'], $resource->availability_zone_id);
    }
}
