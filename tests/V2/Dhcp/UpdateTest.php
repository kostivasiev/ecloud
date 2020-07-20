<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\Dhcp;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
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
        $dhcp = $this->createDhcps();
        $cloud = $this->createCloud();
        $data = [
            'vpc_id'    => $cloud->id,
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
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
        $dhcp = $this->createDhcps();
        $data = [
            'vpc_id'    => '',
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The vpc id field, when specified, cannot be null',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $dhcp = $this->createDhcps();
        $cloud = $this->createCloud();
        $data = [
            'vpc_id'    => $cloud->id,
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $dhcps = Dhcp::findOrFail($dhcp->getKey());
        $this->assertEquals($data['vpc_id'], $dhcps->vpc_id);
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

    /**
     * @return \App\Models\V2\Dhcp
     */
    public function createDhcps(): Dhcp
    {
        $dhcp = factory(Dhcp::class, 1)->create([
            'vpc_id' => $this->createCloud()->id,
        ])->first();
        $dhcp->save();
        $dhcp->refresh();
        return $dhcp;
    }

}