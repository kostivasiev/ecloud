<?php

namespace Tests\V2\LoadBalancerCluster;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $lbc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();

        $this->vpc = factory(Vpc::class)->create([
            'name'    => 'Manchester DC',
            'region_id' => $this->region->getKey()
        ]);

        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->lbc = factory(LoadBalancerCluster::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testInvalidVpcIdIsFailed()
    {
        $data = [
            'name'    => 'My Load Balancer Cluster',
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidavailabilityZoneIsFailed()
    {
        $data = [
            'name'    => 'My Load Balancer Cluster',
            'vpc_id' => $this->faker->uuid(),
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedVpcIdIsFailed()
    {
        $data = [
            'name'    => 'My Load Balancer Cluster',
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/lbcs/' . $this->lbc->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '2-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name'    => 'My Load Balancer Cluster',
            'vpc_id'    => $this->vpc->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ];
        $this->patch(
            '/v2/lbcs/' . $this->lbc->getKey(),
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
