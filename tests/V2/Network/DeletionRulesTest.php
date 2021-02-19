<?php

namespace Tests\V2\Network;

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

class DeletionRulesTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected Nic $nics;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->nics = factory(Nic::class)->create([
            'mac_address' => $this->faker->macAddress,
            'instance_id' => $this->instance()->getKey(),
            'network_id' => $this->network()->getKey(),
            'ip_address' => $this->faker->ipv4,
        ]);
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/networks/' . $this->network()->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
        ])->assertResponseStatus(412);
        $network = Network::withTrashed()->findOrFail($this->network()->getKey());
        $this->assertNull($network->deleted_at);
    }
}
