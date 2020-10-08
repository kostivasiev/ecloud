<?php

namespace Tests\V2\AvailabilityZone;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Dhcp;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetDhcpsTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected AvailabilityZone $availabilityZone;
    protected Dhcp $dhcp;
    protected Region $region;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->availabilityZone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->dhcp = factory(Dhcp::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/availability-zones/'.$this->availabilityZone->getKey().'/dhcps',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id'       => $this->dhcp->id,
                'vpc_id'   => $this->dhcp->vpc_id,
            ])
            ->assertResponseStatus(200);
    }
}
