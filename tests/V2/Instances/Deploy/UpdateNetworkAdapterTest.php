<?php

namespace Tests\V2\Instances\Deploy;

use App\Jobs\Instance\Deploy\Deploy;
use App\Jobs\Instance\Deploy\UpdateNetworkAdapter;
use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Queue;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateNetworkAdapterTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected Instance $instance;
    protected Network $network;
    protected Nic $nic;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->vpc = factory(Vpc::class)->create();
        $region = factory(Region::class)->create();
        $availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $region->getKey(),
        ]);
        $appliance = factory(Appliance::class)->create([
            'appliance_name' => $this->faker->word,
        ])->refresh();
        $applianceVersion = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $appliance->appliance_id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $availabilityZone->getKey(),
            'appliance_version_id' => $applianceVersion->appliance_version_uuid,
        ]);
        $router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'availability_zone_id' => $availabilityZone->getKey(),
        ]);
        $this->network = factory(Network::class)->create([
            'name' => $this->faker->word,
            'router_id' => $router->getKey(),
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id' => $this->network->getKey(),
        ]);
    }

    public function testUpdateNetworkAdapter()
    {
        Queue::fake();

        $this->post('/v2/instances/' . $this->instance->getKey() . '/deploy', [
            'network_id' => $this->network->getKey(),
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(202);

        Queue::assertPushed(Deploy::class, function ($job) {
            return collect($job->chained)->filter(function ($payload) {
                return strpos($payload, UpdateNetworkAdapter::class) !== false;
            })->isNotEmpty();
        });
    }
}
