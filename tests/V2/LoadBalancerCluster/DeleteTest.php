<?php

namespace Tests\V2\LoadBalancerCluster;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancerCluster;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
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
            'name' => 'Manchester DC',
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

    public function testInvalidIdFails()
    {
        $this->delete(
            '/v2/lbcs/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Load Balancer Cluster with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/lbcs/' . $this->lbc->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $resource = LoadBalancerCluster::withTrashed()->findOrFail($this->lbc->getKey());
        $this->assertNotNull($resource->deleted_at);
    }
}
