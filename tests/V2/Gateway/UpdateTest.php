<?php

namespace Tests\V2\Gateway;

use App\Models\V2\Gateway;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class UpdateTest extends TestCase
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
        $zone = $this->createGateway();
        $data = [
            'name' => 'Manchester Gateway 2',
        ];
        $this->patch(
            '/v2/gateways/' . $zone->getKey(),
            $data,
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

    public function testNullNameIsDenied()
    {
        $zone = $this->createGateway();
        $data = [
            'name' => '',
        ];
        $this->patch(
            '/v2/gateways/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $zone = $this->createGateway();
        $data = [
            'name' => 'Manchester Gateway 2',
        ];
        $this->patch(
            '/v2/gateways/' . $zone->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $gatewayItem = Gateway::findOrFail($zone->getKey());
        $this->assertEquals($data['name'], $gatewayItem->name);
    }

    /**
     * Create Gateway
     * @return \App\Models\V2\Gateway
     */
    public function createGateway(): Gateway
    {
        $gateway = factory(Gateway::class, 1)->create()->first();
        $gateway->save();
        $gateway->refresh();
        return $gateway;
    }

}