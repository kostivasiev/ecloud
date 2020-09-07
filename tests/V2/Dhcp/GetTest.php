<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
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
        $this->get(
            '/v2/dhcps',
            []
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
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id'    => $vpc->id,
        ]);
        $this->get(
            '/v2/dhcps',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $dhcp->id,
                'vpc_id' => $dhcp->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $vpc = factory(Vpc::class)->create();
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $vpc->id,
        ]);
        $this->get(
            '/v2/dhcps/' . $dhcp->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $dhcp->id,
                'vpc_id' => $dhcp->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

}
