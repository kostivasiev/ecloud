<?php

namespace Tests\V2\Instances;

use App\Events\V2\Sync\Created;
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
use Illuminate\Support\Facades\Event;
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

        $this->kingpinServiceMock()->shouldReceive('get')->andReturn(
            new Response(200, [], json_encode(['powerState' => 'poweredOn']))
        );
    }

    public function testGetCollection()
    {
        $this->nic();

        $this->get(
            '/v2/instances/' . $this->instance()->id . '/nics',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'          => $this->nic()->id,
                'mac_address' => $this->nic()->mac_address,
                'instance_id' => $this->nic()->instance_id,
                'network_id'  => $this->nic()->network_id,
            ])
            ->assertResponseStatus(200);
    }
}
