<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use App\Models\V2\Region;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\Mocks\Traits\NetworkingApio;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations, NetworkingApio;

    public function setUp(): void
    {
        parent::setUp();
        $this->networkingApioSetup();
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'vpc_id' => $this->vpc()->id
        ];
        $this->post(
            '/v2/floating-ips',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(201);

        $id = (json_decode($this->response->getContent()))->data->id;
        $floatingIp = FloatingIp::findOrFail($id);
        $this->assertEquals('complete', $floatingIp->getStatus());
    }
}
