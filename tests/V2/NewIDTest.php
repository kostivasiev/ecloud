<?php

namespace Tests\V2;

use App\Models\V2\AvailabilityZones;
use App\Models\V2\Gateways;
use App\Models\V2\Routers;
use App\Models\V2\VirtualPrivateClouds;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class NewIDTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testFormatOfAvailabilityZoneID()
    {
        $data = [
            'code'    => 'MAN1',
            'name'    => 'Manchester Zone 1',
            'site_id' => $this->faker->randomDigit(),
        ];
        $this->post(
            '/v2/availability-zones',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(AvailabilityZones::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfGatewaysId()
    {
        $data = [
            'name'    => 'Manchester Gateway 1',
        ];
        $this->post(
            '/v2/gateways',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(Gateways::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfRoutersId()
    {
        $data = [
            'name'    => 'Manchester Router 1',
        ];
        $this->post(
            '/v2/routers',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(Routers::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testFormatOfVirtualDatacentresId()
    {
        $data = [
            'name'    => 'Manchester DC',
        ];
        $this->post(
            '/v2/vpcs',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);
        $this->assertRegExp(
            $this->generateRegExp(VirtualPrivateClouds::class),
            (json_decode($this->response->getContent()))->data->id
        );
    }

    public function testAvailabilityZonesRouterAssociation()
    {
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);

        // test that the association has occurred
        $router->refresh();
        $associated = $availabilityZones->routers()->first();
        $this->assertEquals($associated->toArray(), $router->toArray());

        // Test IDs
        $this->assertRegExp(
            $this->generateRegExp(AvailabilityZones::class),
            $availabilityZones->id
        );
        $this->assertRegExp(
            $this->generateRegExp(Routers::class),
            $router->id
        );
    }

    public function testAvailabilityZonesRouterDisassociation()
    {
        $availabilityZones = (factory(AvailabilityZones::class, 1)->create()->first())->refresh();
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $availabilityZones->routers()->attach($router->getKey());
        $this->delete(
            '/v2/availability-zones/' . $availabilityZones->getKey() . '/routers/' . $router->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $router->refresh();
        $this->assertEquals(0, $availabilityZones->routers()->count());

        // Test IDs
        $this->assertRegExp(
            $this->generateRegExp(AvailabilityZones::class),
            $availabilityZones->id
        );
        $this->assertRegExp(
            $this->generateRegExp(Routers::class),
            $router->id
        );
    }

    public function testRoutersGatewaysAssociation()
    {
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $gateway = (factory(Gateways::class, 1)->create()->first())->refresh();
        $this->put(
            '/v2/routers/' . $router->id . '/gateways/' . $gateway->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);

        // test that the association has occurred
        $router->refresh();
        $associated = $router->gateways()->first();
        $this->assertEquals($associated->toArray(), $gateway->toArray());

        // Test IDs
        $this->assertRegExp(
            $this->generateRegExp(Routers::class),
            $router->id
        );
        $this->assertRegExp(
            $this->generateRegExp(Gateways::class),
            $gateway->id
        );
    }

    public function testRoutersGatewaysDisassociation()
    {
        $router = (factory(Routers::class, 1)->create()->first())->refresh();
        $gateway = (factory(Gateways::class, 1)->create()->first())->refresh();
        $router->gateways()->attach($gateway->id);
        $this->delete(
            '/v2/routers/' . $router->id . '/gateways/' . $gateway->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $router->refresh();
        $this->assertEquals(0, $router->gateways()->count());

        // Test IDs
        $this->assertRegExp(
            $this->generateRegExp(Routers::class),
            $router->id
        );
        $this->assertRegExp(
            $this->generateRegExp(Gateways::class),
            $gateway->id
        );
    }

    /**
     * Generates a regular expression based on the speciied model's prefix
     * @param $model
     * @return string
     */
    public function generateRegExp($model): string
    {
        return "/^" . $model::KEY_PREFIX . "\-[a-f0-9]{" . ($model::$keyLength * 2) . "}$/i";
    }

}