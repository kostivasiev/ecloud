<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallRule\Deleted;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected FirewallPolicy $firewall_policy;
    protected FirewallRule $firewall_rule;
    protected Region $region;
    protected Vpc $vpc;
    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();
        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'reseller_id' => 3,
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
        $this->firewall_policy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id,
        ]);
        $this->firewall_rule = factory(FirewallRule::class)->create([
            'firewall_policy_id' => $this->firewall_policy->id,
        ]);
    }

    public function testFailInvalidId()
    {
        $this->delete(
            '/v2/firewall-rules/' . $this->faker->uuid,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->seeJson([
                'title' => 'Not found',
                'detail' => 'No Firewall Rule with that ID was found',
                'status' => 404,
            ])
            ->assertResponseStatus(404);
    }

    public function testSuccessfulDelete()
    {
        $this->delete(
            '/v2/firewall-rules/' . $this->firewall_rule->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )
            ->assertResponseStatus(204);
        $instance = FirewallRule::withTrashed()->findOrFail($this->firewall_rule->id);
        $this->assertNotNull($instance->deleted_at);

        Event::assertDispatched(Deleted::class, function ($job) {
            return $job->model->id === $this->firewall_rule->id;
        });
    }

}
