<?php

namespace Tests\V2\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Models\V2\LoadBalancer;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    protected $faker;
    protected $region;
    protected $vpc;
    protected $availabilityZone;
    protected $loadBalancer;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->loadBalancer = LoadBalancer::factory()->create([
            'availability_zone_id' => $this->availabilityZone()->id,
            'vpc_id' => $this->vpc()->id
        ]);
    }

    public function testValidDataSucceeds()
    {
        Event::fake(Created::class);

        $data = [
            'name' => 'My Load Balancer Cluster',
        ];
        $this->patch(
            '/v2/load-balancers/' . $this->loadBalancer->id,
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeInDatabase(
                'load_balancers',
                $data,
                'ecloud'
            )
            ->assertResponseStatus(202);
    }
}
