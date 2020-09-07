<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\FirewallRule;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/firewall-rules/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Firewall Rule with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $rule = factory(FirewallRule::class)->create();
        $this->delete(
            '/v2/firewall-rules/' . $rule->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = FirewallRule::withTrashed()->findOrFail($rule->getKey());
        $this->assertNotNull($instance->deleted_at);
    }

}
