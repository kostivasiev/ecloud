<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\LoadBalancer;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetClustersTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected LoadBalancer $loadBalancer;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->loadBalancer = factory(LoadBalancer::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/' . $this->availabilityZone()->id . '/load-balancers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->loadBalancer->id,
                'name'   => $this->loadBalancer->name,
                'vpc_id' => $this->loadBalancer->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer->load_balancer_spec_id,
            ])
            ->assertResponseStatus(200);
    }
}
