<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
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
        Nic::withoutEvents(function() {
            $this->nics = factory(Nic::class)->create([
                'id' => 'nic-test',
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $this->instance()->id,
                'network_id' => $this->network()->id,
                'ip_address' => $this->faker->ipv4,
            ]);
        });
    }

    public function testFailedDeletion()
    {
        $this->delete(
            '/v2/networks/' . $this->network()->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted',
        ])->assertResponseStatus(412);
        $network = Network::withTrashed()->findOrFail($this->network()->id);
        $this->assertNull($network->deleted_at);
    }
}
