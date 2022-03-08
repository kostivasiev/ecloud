<?php

namespace Tests\V2\Network;

use App\Models\V2\Network;
use App\Models\V2\Nic;
use Faker\Factory as Faker;
use Tests\TestCase;

class DeletionRulesTest extends TestCase
{
    protected \Faker\Generator $faker;
    protected Nic $nics;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        Nic::withoutEvents(function () {
            $this->nics = Nic::factory()->create([
                'id' => 'nic-test',
                'mac_address' => $this->faker->macAddress,
                'instance_id' => $this->instanceModel()->id,
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
        )->assertJsonFragment([
            'detail' => 'The specified resource has dependant relationships and cannot be deleted: ' . $this->nics->id,
        ])->assertStatus(412);
        $network = Network::withTrashed()->findOrFail($this->network()->id);
        $this->assertNull($network->deleted_at);
    }
}
