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

    public function testValidDataIsSuccessful()
    {
        $newName = $this->faker->word;

        $this->patch(
            '/v2/floating-ips/' . $this->floatingIp->id,
            [
                'name' => $newName
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'floating_ips',
                [
                    'id' => $this->floatingIp->id,
                    'name' => $newName
                ],
                'ecloud'
            )
            ->assertResponseStatus(200);
    }

}
