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

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

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
        $nic = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance()->id,
            'network_id' => $this->network()->id,
        ]);

        $this->get(
            '/v2/instances/' . $this->instance()->getKey() . '/nics',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'          => $nic->getKey(),
                'mac_address' => $nic->mac_address,
                'instance_id' => $nic->instance_id,
                'network_id'  => $nic->network_id,
            ])
            ->assertResponseStatus(200);
    }
}
