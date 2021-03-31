<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\Traits\NetworkingApio;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations, NetworkingApio;

    protected $floatingIp;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkingApioSetup();
        $this->floatingIp = factory(FloatingIp::class)->create([
            'vpc_id' => $this->vpc()->id,
            'ip_address' => '0.0.0.1',
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
                'id' => $this->floatingIp->id,
                'name' => $this->floatingIp->id,
                'vpc_id' => $this->vpc()->id,
                'ip_address' => $this->floatingIp->ip_address
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/floating-ips/' . $this->floatingIp->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->floatingIp->id,
                'name' => $this->floatingIp->id,
                'vpc_id' => $this->vpc()->id,
                'ip_address' => $this->floatingIp->ip_address
            ])
            ->assertResponseStatus(200);
    }
}
