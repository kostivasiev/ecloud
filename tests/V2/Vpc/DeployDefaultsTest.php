<?php

namespace Tests\V2\Vpc;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Network;
use App\Models\V2\Region;
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
        $region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $region->id,
        ]);
        $vpc = factory(Vpc::class, 1)->create([
            'region_id' => $region->id,
        ])->first();
        $this->post(
            '/v2/vpcs/' . $vpc->id . '/deploy-defaults',
            [],
            [
                'X-consumer-custom-id' => '1-1',
                'X-consumer-groups'    => 'ecloud.write'
            ]
        )
            ->assertResponseStatus(202);

        // Check the relationships are intact
        $vpc = Vpc::findOrFail($vpc->id);
        $router = $vpc->router()->first();
        $this->assertNotNull($router);
        $this->assertNotNull(Network::where('router_id', '=', $router->id)->first());
    }
}
