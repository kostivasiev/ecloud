<?php

namespace Tests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;

class GetClustersTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected LoadBalancer $loadBalancer;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->loadBalancer = LoadBalancer::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/vpcs/' . $this->vpc()->id . '/load-balancers',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->assertJsonFragment([
                'id'     => $this->loadBalancer->id,
                'name'   => $this->loadBalancer->name,
                'vpc_id' => $this->loadBalancer->vpc_id,
                'load_balancer_spec_id' => $this->loadBalancer->load_balancer_spec_id,
            ])
            ->assertStatus(200);
    }
}
