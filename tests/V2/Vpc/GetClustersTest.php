<?php

namespace Tests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Faker\Generator;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetClustersTest extends TestCase
{
    use DatabaseMigrations;

    protected Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected LoadBalancerCluster $lbc;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $region = factory(Region::class)->create();
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $region->getKey()
        ]);

        $this->lbc = factory(LoadBalancerCluster::class)->create([
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/vpcs/' . $this->vpc->getKey() . '/lbcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->lbc->getKey(),
                'name'   => $this->lbc->name,
                'vpc_id' => $this->lbc->vpc_id,
                'nodes'  => $this->lbc->nodes,
            ])
            ->assertResponseStatus(200);
    }
}
