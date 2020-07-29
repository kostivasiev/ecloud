<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallRule;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testGetCollection()
    {
        $instance = factory(FirewallRule::class, 1)->create([
            'name' => 'Demo firewall rule 1',
        ])->first();
        $this->get(
            '/v2/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $instance->id,
                'name'       => $instance->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $instance = factory(FirewallRule::class, 1)->create([
            'name' => 'Demo firewall rule 1',
        ])->first();
        $instance->save();
        $instance->refresh();

        $this->get(
            '/v2/firewall-rules/' . $instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $instance->id,
                'name'       => $instance->name,
            ])
            ->assertResponseStatus(200);
    }

}
