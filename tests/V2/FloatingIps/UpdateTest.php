<?php

namespace Tests\V2\FloatingIps;

use App\Models\V2\FloatingIp;
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
        $this->floatingIp = factory(FloatingIp::class)->create();
    }

    public function testNoPermsIsDenied()
    {
        $data = [];

        $this->patch(
            '/v2/floating-ips/' . $this->floatingIp->getKey(),
            $data,
            []
        )
            ->seeJson([
                'title' => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testValidDataIsSuccessful()
    {
        $data = [];

        $this->patch(
            '/v2/floating-ips/' . $this->floatingIp->getKey(),
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);
    }

}
