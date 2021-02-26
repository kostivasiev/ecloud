<?php

namespace Tests\V2\LoadBalancerCluster\EventTests;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Faker\Generator;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DefaultAvailabilityZoneTest extends TestCase
{
    use DatabaseMigrations;

    protected Generator $faker;
    protected AvailabilityZone $availability_zone;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create([
            'name' => $this->faker->country(),
        ]);
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id,
        ]);
    }

    public function testCreateLbcWithAvailabilityZone()
    {
        $this->post(
            '/v2/lbcs',
            [
                'name' => 'My Load Balancer Cluster',
                'vpc_id' => $this->vpc->id,
                'availability_zone_id' => $this->availability_zone->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $id = json_decode($this->response->getContent())->data->id;
        $lbcs = LoadBalancerCluster::findOrFail($id);
        // verify that the availability_zone_id equals the one in the data array
        $this->assertEquals($lbcs->availability_zone_id, $this->availability_zone->id);
    }

    public function testCreateLbcWithNoAvailabilityZone()
    {
        $this->post(
            '/v2/lbcs',
            [
                'name' => 'My Load Balancer Cluster',
                'vpc_id' => $this->vpc->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $id = json_decode($this->response->getContent())->data->id;
        $lbcs = LoadBalancerCluster::findOrFail($id);
        // verify that the availability_zone_id equals the one defined in setUp()
        $this->assertEquals($lbcs->availability_zone_id, $this->availability_zone->id);
    }
}
