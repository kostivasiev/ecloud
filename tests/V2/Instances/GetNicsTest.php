<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Nic;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetNicsTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected Appliance $appliance;
    protected ApplianceVersion $appliance_version;
    protected AvailabilityZone $availability_zone;
    protected Instance $instance;
    protected Network $network;
    protected Nic $nic;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->appliance = factory(Appliance::class)->create([
            'appliance_name' => 'Test Appliance',
        ])->refresh();
        $this->appliance_version = factory(ApplianceVersion::class)->create([
            'appliance_version_appliance_id' => $this->appliance->id,
        ])->refresh();
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
            'appliance_version_id' => $this->appliance_version->uuid,
            'vcpu_cores' => 1,
            'ram_capacity' => 1024,
            'platform' => 'Linux',
        ]);
        $this->network = factory(Network::class)->create([
            'name' => 'Manchester Network',
        ]);
        $this->nic = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance->getKey(),
            'network_id' => $this->network->getKey(),
        ]);

        $mockKingpinService = \Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode(['powerState' => 'poweredOn']))
        );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/instances/'.$this->instance->getKey().'/nics',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'          => $this->nic->getKey(),
                'mac_address' => $this->nic->mac_address,
                'instance_id' => $this->nic->instance_id,
                'network_id'  => $this->nic->network_id,
            ])
            ->assertResponseStatus(200);
    }
}
