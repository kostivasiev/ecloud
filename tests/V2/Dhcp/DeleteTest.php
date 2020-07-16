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
        $dhcps = factory(Dhcp::class, 1)->create([
            'vpc_id' => $this->createCloud()->id,
        ])->first();
        $dhcps->refresh();
        $this->delete(
            '/v2/dhcps/' . $dhcps->getKey(),
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
        $dhcps = factory(Dhcp::class, 1)->create([
            'vpc_id' => $this->createCloud()->id,
        ])->first();
        $dhcps->refresh();
        $this->delete(
            '/v2/dhcps/' . $dhcps->getKey(),
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $network = Dhcp::withTrashed()->findOrFail($dhcps->getKey());
        $this->assertNotNull($network->deleted_at);
    }

    /**
     * Create VirtualPrivateClouds
     * @return \App\Models\V2\Vpc
     */
    public function createCloud(): Vpc
    {
        $cloud = factory(Vpc::class, 1)->create()->first();
        $cloud->save();
        $cloud->refresh();
        return $cloud;
    }

}
