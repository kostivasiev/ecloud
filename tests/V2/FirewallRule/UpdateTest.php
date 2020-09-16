<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
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
    }

    public function testNullNameIsFailed()
    {
        $fwr = factory(FirewallRule::class)->create();
        $data = [
            'name' => '',
        ];
        $this->patch(
            '/v2/firewall-rules/' . $fwr->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field, when specified, cannot be null',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testNotOwnedRouterIdIsFailed()
    {
        $fwr = factory(FirewallRule::class)->create();
        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'code'               => 'TIM1',
            'name'               => 'Tims Region 1',
            'datacentre_site_id' => 1,
            'region_id'          => $this->region->getKey(),
        ]);
        $vpc = factory(Vpc::class)->create([
            'name' => 'Manchester DC',
            'reseller_id' => 3,
            'region_id' => $this->region->getKey()
        ]);
        $router = factory(Router::class)->create([
            'name' => 'Manchester Router 1',
            'vpc_id' => $vpc->getKey()
        ]);

        $data = [
            'name' => $this->faker->word(),
            'router_id' => $router->getKey()
        ];
        $this->patch(
            '/v2/firewall-rules/' . $fwr->id,
            $data,
            [
                'X-consumer-custom-id' => '1-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The specified router id was not found',
                'status' => 422,
                'source' => 'router_id'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $fwr = factory(FirewallRule::class)->create();
        $data = [
            'name' => 'Demo firewall rule 1',
        ];
        $this->patch(
            '/v2/firewall-rules/' . $fwr->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(200);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);
    }

}
