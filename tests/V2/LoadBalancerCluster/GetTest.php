<?php

namespace Tests\V2\LoadBalancerCluster;

use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\LoadBalancerSpecification;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    protected $lbcs;
    protected $lbs;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->lbs = factory(LoadBalancerSpecification::class)->create();
        $this->lbc = factory(LoadBalancerCluster::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id,
            'lbs_id' => $this->lbs->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/load-balancers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->lbc->id,
                'name' => $this->lbc->name,
                'vpc_id' => $this->lbc->vpc_id,
                'lbs_id' => $this->lbc->lbs_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/load-balancers/' . $this->lbc->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->lbc->id,
                'name' => $this->lbc->name,
                'vpc_id' => $this->lbc->vpc_id,
                'lbs_id' => $this->lbc->lbs_id,
            ])
            ->assertResponseStatus(200);
    }

}
