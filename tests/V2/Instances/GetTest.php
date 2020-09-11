<?php

namespace Tests\V2\Instances;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Tests\TestCase;
use Laravel\Lumen\Testing\DatabaseMigrations;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected $faker;

    protected $instance;

    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        Vpc::flushEventListeners();
        $this->vpc = factory(Vpc::class)->create([
            'name' => 'Manchester VPC',
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
        ]);
    }

    public function testNoPermsIsDenied()
    {
        $this->get(
            '/v2/instances',
            []
        )
            ->seeJson([
                'title'  => 'Unauthorised',
                'detail' => 'Unauthorised',
                'status' => 401,
            ])
            ->assertResponseStatus(401);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/instances',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'name' => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/instances/' . $this->instance->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->instance->getKey(),
                'name' => $this->instance->name,
                'vpc_id' => $this->instance->vpc_id,
            ])
            ->assertResponseStatus(200);
    }
}
