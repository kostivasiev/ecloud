<?php

namespace Tests\V2\LoadBalancer;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\LoadBalancer;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;

class DeleteTest extends TestCase
{
    protected $faker;
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $loadBalancer;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = Region::factory()->create();

        $this->vpc = Vpc::withoutEvents(function () {
            return Vpc::factory()->create([
                'id' => 'vpc-test',
                'name' => 'Manchester DC',
                'region_id' => $this->region->id
            ]);
        });

        $this->availabilityZone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);

        $this->loadBalancer = LoadBalancer::factory()->create([
            'availability_zone_id' => $this->availabilityZone->id,
            'vpc_id' => $this->vpc->id
        ]);
    }

    public function testInvalidIdFails()
    {
        $this->delete(
            '/v2/load-balancers/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertJsonFragment([
                'title' => 'Not found',
                'detail' => 'No Load Balancer with that ID was found',
                'status' => 404,
            ])
            ->assertStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->loadBalancer->setAttribute('config_id', 123456)
            ->saveQuietly();

        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')
                ->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('deleteById')
                    ->with(123456)
                    ->andReturnTrue();
                return $clusterMock;
            });
            return $mock;
        });

        $this->delete(
            '/v2/load-balancers/' . $this->loadBalancer->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertStatus(202);

        $resource = LoadBalancer::withTrashed()->findOrFail($this->loadBalancer->id);
        $this->assertNotNull($resource->deleted_at);
    }
}
