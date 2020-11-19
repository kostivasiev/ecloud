<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $region;
    protected $vpc;
    protected $faker;
    protected $floatingIp;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/floating-ips',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->floatingIp->getKey(),
                'name' => $this->floatingIp->getKey(),
                'vpc_id' => $this->vpc->getKey(),
                'ip_address' => $this->floatingIp->ip_address
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/floating-ips/' . $this->floatingIp->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->floatingIp->getKey(),
                'name' => $this->floatingIp->getKey(),
                'vpc_id' => $this->vpc->getKey(),
                'ip_address' => $this->floatingIp->ip_address
            ])
            ->assertResponseStatus(200);
    }
}
