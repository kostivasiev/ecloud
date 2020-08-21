<?php

namespace Tests\V2\Network;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $vpc;

    protected $router;

    protected $availabilityZone;

    public function setUp(): void
    {
        parent::setUp();

        $this->faker = Faker::create();

        $this->vpc = factory(Vpc::class)->create([
            'name'    => 'Manchester DC',
        ]);

        $this->router = factory(Router::class)->create([
            'name'       => 'Manchester Router 1',
            'vpc_id' => $this->vpc->getKey()
        ]);

        $this->availabilityZone = factory(AvailabilityZone::class)->create();
    }

    public function testNoPermsIsDenied()
    {
        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Network',
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            []
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
        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => '',
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
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


    public function testInvalidRouterIdIsFailed()
    {
        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Network',
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'router_id' => $this->faker->uuid()
        ];

        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $vpc = factory(Vpc::class)->create(['reseller_id' => 3]);
        $router = factory(Router::class)->create([
            'vpc_id' => $vpc->getKey()
        ]);

        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Network',
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'router_id' => $router->getKey()
        ];

        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testInvalidAvailabilityZoneIdIsFailed()
    {
        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Network',
            'availability_zone_id' => $this->faker->uuid(),
            'router_id' => $this->router->getKey()
        ];

        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified availability zone id was not found',
                'status' => 422,
                'source' => 'availability_zone_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $net = factory(Network::class)->create([
            'router_id' => $this->router->getKey(),
            'availability_zone_id' => $this->availabilityZone->getKey()
        ]);
        $data = [
            'name'    => 'Manchester Network',
            'availability_zone_id' => $this->availabilityZone->getKey(),
            'router_id' => $this->router->getKey()
        ];
        $this->patch(
            '/v2/networks/' . $net->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $networks = Network::findOrFail($net->getKey());
        $this->assertEquals($data['name'], $networks->name);
    }
}
