<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->floatingIp = factory(FloatingIp::class, 1)->create()->first();
    }

    public function testNoPermsIsDenied()
    {
        $data = [];
        $this->post(
            '/v2/floating-ips',
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

    public function testValidDataSucceeds()
    {
        $data = [];
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
        $this->seeJson([
            'id' => $id,
        ]);
    }
}
