<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class RuntimePropertyTests extends TestCase
{
    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = Region::factory()->create();
        $this->availability_zone = AvailabilityZone::factory()->create([
            'region_id' => $this->region->id
        ]);
        $this->instance = Instance::factory()->create([
            'vpc_id' => $this->vpc()->id,
            'name' => 'GetTest Default',
        ]);

        $mockKingpinService = Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode([
                'powerState' => 'poweredOn',
                'toolsRunningStatus' => 'guestToolsRunning'
            ]))
        );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    /**
     * Test agent_running is not returned on the collection
     */
    public function testRuntimePropertiesNotInCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonMissing([
            'agent_running' => true,
            'online' => true,
        ])->assertStatus(200);
    }

    /**
     * Test agent_running is not returned on the collection
     */
    public function testRuntimePropertiesNullWithKingpinException()
    {
        $mockKingpinService = Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('get')->andThrow(
            new \Exception("test exception")
        );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });

        $this->get(
            '/v2/instances/' . $this->instance->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'online' => null,
            'agent_running' => null,
        ])->assertStatus(200);
    }

    /**
     * Test agent_running is returned on the model
     */
    public function testGetRuntimePropertiesInItem()
    {
        $this->get(
            '/v2/instances/' . $this->instance->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->assertJsonFragment([
            'agent_running' => true,
            'online' => true,
        ])->assertStatus(200);
    }
}
