<?php

namespace Tests\V2\Instances;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use App\Services\V2\KingpinService;
use GuzzleHttp\Client;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class RuntimePropertyTests extends TestCase
{
    use DatabaseMigrations;

    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'GetTest Default',
        ]);
        $mockKingpinService = Mockery::mock(new KingpinService(new Client()))->makePartial();
        $mockKingpinService->shouldReceive('get')->andReturn(
            (object)['powerState' => 'poweredOn', 'toolsRunningStatus' => 'guestToolsRunning']
        );
        app()->bind(KingpinService::class, function () use ($mockKingpinService) {
            return $mockKingpinService;
        });
    }

    /**
     * Test agent_running is not returned on the collection
     */
    public function testGetAgentRunningNotInCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->dontSeeJson([
                'agent_running' => true,
            ])
            ->assertResponseStatus(200);
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
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'online' => null,
                'agent_running' => null,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Test agent_running is returned on the model
     */
    public function testGetAgentRunningStateInItem()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'agent_running' => true,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Test agent_running is not returned on the collection
     */
    public function testGetOnlineNotInCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->dontSeeJson([
                'online' => true,
            ])
            ->assertResponseStatus(200);
    }

    /**
     * Test agent_running is returned on the model
     */
    public function testGetOnlineInItem()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'online' => true,
            ])
            ->assertResponseStatus(200);
    }
}
