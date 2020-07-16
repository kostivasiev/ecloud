<?php

namespace Tests\V2\Gateway;

use App\Models\V2\Gateway;
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

    public function testNonAdminIsDenied()
    {
        $this->get(
            '/v2/gateways',
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $gatewayItem = factory(Gateway::class, 1)->create([
            'name'       => 'Manchester Gateway 1',
        ])->first();
        $this->get(
            '/v2/gateways',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $gatewayItem->id,
                'name'       => $gatewayItem->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $gateway = factory(Gateway::class, 1)->create([
            'name'       => 'Manchester Gateway 1',
        ])->first();
        $gateway->save();
        $gateway->refresh();

        $this->get(
            '/v2/gateways/' . $gateway->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'         => $gateway->id,
                'name'       => $gateway->name,
            ])
            ->assertResponseStatus(200);
    }

}
