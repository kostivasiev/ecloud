<?php

namespace Tests\V2\Vpc;

use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Faker\Generator;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class DeployDefaultsTest extends TestCase
{

    use DatabaseMigrations;

    protected Generator $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
    }

    public function testInvalidVpcId()
    {
        $this->post(
            '/v2/vpcs/' . $this->faker->uuid() . '/deploy-defaults',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.write'
            ]
        )
            ->seeJson([
                'title'  => 'Not found',
                'detail' => 'No Vpc with that ID was found'
            ])
            ->assertResponseStatus(404);
    }

    public function testValidDeploy()
    {
        $vpc = factory(Vpc::class, 1)->create()->first();
        $this->post(
            '/v2/vpcs/' . $vpc->id . '/deploy-defaults',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.write'
            ]
        );
        dd(
            $this->response->getStatusCode(),
            json_decode($this->response->getContent(), true)
        );
    }
}
