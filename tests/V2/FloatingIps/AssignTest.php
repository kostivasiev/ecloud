<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AssignTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $floatingIp;
    protected $nic;
    protected $nat;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);

        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
            'reseller_id' => 1
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
            'router_id' => $this->router->getKey()
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id' => $this->network->getKey(),
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testAssignIsSuccessful()
    {
        $this->post(
            '/v2/floating-ips/' . $this->floatingIp->getKey() . '/assign',
            [
                'resource_id' => $this->nic->getKey()
            ],
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'nats',
            [
                'destination_id' => $this->floatingIp->id,
                'destinationable_type' => 'fip',
                'translated_id' => $this->nic->id,
                'translatedable_type' => 'nic'
            ],
            'ecloud'
        )
            ->assertResponseStatus(200);

        $this->assertEquals($this->nic->getKey(), $this->floatingIp->resourceId);

        $this->get(
            '/v2/floating-ips/' . $this->floatingIp->getKey(),
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->floatingIp->getKey(),
                'resource_id' => $this->nic->getKey()
            ])
            ->assertResponseStatus(200);
    }

}
