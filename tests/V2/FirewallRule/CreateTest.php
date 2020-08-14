<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    /**
     * @var Vpc
     */
    private $vpc;

    /**
     * @var Router
     */
    private $router;

    /**
     * @var \Faker\Generator
     */
    protected $faker;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->vpc = factory(Vpc::class, 1)->create([
            'name' => 'Manchester DC',
        ])->first();

        $this->router = factory(Router::class, 1)->create([
            'name' => 'Manchester Router 1',
            'vpc_id' => $this->vpc->getKey()
        ])->first();
    }

    public function testNullNameIsFailed()
    {
        $data = [
            'name' => '',
        ];
        $this->post(
            '/v2/firewall-rules',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title'  => 'Validation Error',
                'detail' => 'The name field is required',
                'status' => 422,
                'source' => 'name'
            ])
            ->assertResponseStatus(422);
    }

    public function testValidDataSucceeds()
    {
        $data = [
            'name' => 'Demo firewall rule 1',
            'router_id' => $this->router->getKey()
        ];
        $this->post(
            '/v2/firewall-rules',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups'    => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $availabilityZoneId = (json_decode($this->response->getContent()))->data->id;
        $this->seeJson([
            'id' => $availabilityZoneId,
        ]);
    }

}