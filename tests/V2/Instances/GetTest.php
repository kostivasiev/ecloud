<?php

namespace Tests\V2\Instances;

use App\Models\V2\Appliance;
use App\Models\V2\ApplianceVersion;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Providers\EncryptionServiceProvider;
use App\Services\V2\KingpinService;
use Faker\Factory as Faker;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;
use UKFast\Admin\Devices\AdminClient;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected $availability_zone;
    protected $instance;
    protected $appliance;
    protected $appliance_version;
    protected $region;
    protected $vpc;

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
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->instance->getKey(),
                'name'   => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
            ])
            ->assertResponseStatus(200);

        // Test to ensure you don't see platform attribute
        $this->dontSeeJson([
            'platform' => 'Linux',
        ]);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $this->instance->getKey(),
                'name'   => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
                'appliance_version_id' => $this->appliance_version->uuid,
            ])
            ->assertResponseStatus(200);

        $result = json_decode($this->response->getContent());

        // Test to ensure appliance_id as a UUID is in the returned result
        $this->assertEquals($this->appliance->uuid, $result->data->appliance_id);

        // Test to ensure that platform attribute is present
        $this->seeJson([
            'platform' => 'Linux',
        ]);
    }
}
