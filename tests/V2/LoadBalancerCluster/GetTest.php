<?php

namespace Tests\V2\LoadBalancerCluster;

use App\Models\V2\LoadBalancerCluster;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $lbcs;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->lbc = factory(LoadBalancerCluster::class)->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/lbcs',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->lbc->id,
                'name' => $this->lbc->name,
                'vpc_id' => $this->lbc->vpc_id,
                'nodes' => $this->lbc->nodes,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/lbcs/' . $this->lbc->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->lbc->id,
                'name' => $this->lbc->name,
                'vpc_id' => $this->lbc->vpc_id
            ])
            ->assertResponseStatus(200);
    }

}
