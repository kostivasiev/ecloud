<?php

namespace Tests\V2\Gateways;

use App\Models\V2\Gateways;
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

    public function testNonAdminIsDenied()
    {
        $gateway = factory(Gateways::class, 1)->create()->first();
        $gateway->refresh();
        $this->delete(
            '/v2/gateways/' . $gateway->getKey(),
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.write',
            ]
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
            '/v2/gateways/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Gateways with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $gateway = factory(Gateways::class, 1)->create()->first();
        $gateway->refresh();
        $this->delete(
            '/v2/gateways/' . $gateway->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $gatewayItem = Gateways::withTrashed()->findOrFail($gateway->getKey());
        $this->assertNotNull($gatewayItem->deleted_at);
    }

}
