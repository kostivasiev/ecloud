<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testNoPermsIsDenied()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $this->delete(
            '/v2/dhcps/' . $dhcp->getKey(),
            [],
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/dhcps/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Dhcp with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $this->delete(
            '/v2/dhcps/' . $dhcp->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $network = Dhcp::withTrashed()->findOrFail($dhcp->getKey());
        $this->assertNotNull($network->deleted_at);
    }
}
