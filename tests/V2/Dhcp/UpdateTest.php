<?php

namespace Tests\V2\Dhcp;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;
    protected $availability_zone;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create([
            'name' => $this->faker->country(),
        ]);
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ])->refresh();
    }

    public function testNoPermsIsDenied()
    {
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
        $data = [
            'vpc_id' => $this->vpc->id,
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
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
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

    public function testNotOwnedVpcIsFailed()
    {
        $vpc2 = factory(Vpc::class)->create([
            'reseller_id' => 3,
            'region_id' => $this->region->getKey()
        ]);
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
        $data = [
            'vpc_id'    => $vpc2->getKey(),
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified vpc id was not found',
                'status' => 422,
                'source' => 'vpc_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataIsSuccessful()
    {
        $dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->id,
        ]);
        $data = [
            'vpc_id' => $this->vpc->id,
        ];
        $this->patch(
            '/v2/dhcps/' . $dhcp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);

        $dhcp = Dhcp::findOrFail($dhcp->getKey());
        $this->assertEquals($data['vpc_id'], $dhcp->vpc_id);
    }
}
