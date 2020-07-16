<?php

namespace Tests\V2\Dhcps;

use App\Models\V2\Dhcps;
use App\Models\V2\VirtualPrivateClouds;
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
        $cloud = factory(VirtualPrivateClouds::class, 1)->create()->first();
        $cloud->save();
        $cloud->refresh();
        $dhcps = factory(Dhcps::class, 1)->create([
            'vpc_id'    => $cloud->id,
        ])->first();
        $this->get(
            '/v2/dhcps',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $dhcps->id,
                'vpc_id' => $dhcps->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $cloud = factory(VirtualPrivateClouds::class, 1)->create()->first();
        $cloud->save();
        $cloud->refresh();
        $dhcps = factory(Dhcps::class, 1)->create([
            'vpc_id'    => $cloud->id,
        ])->first();
        $dhcps->save();
        $dhcps->refresh();

        $this->get(
            '/v2/dhcps/' . $dhcps->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'     => $dhcps->id,
                'vpc_id' => $dhcps->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

}
