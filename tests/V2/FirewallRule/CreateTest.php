<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallRule\Saved as FirewallRuleSaved;
use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected $availability_zone;
    protected $faker;
    protected $firewall_policy;
    protected $region;
    protected $router;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->id,
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->id
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->id
        ]);
        $this->firewall_policy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id,
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(201);

        $firewallRuleId = (json_decode($this->response->getContent()))->data->id;

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewall_policy->id;
        });

        Event::assertDispatched(FirewallRuleSaved::class, function ($job) use ($firewallRuleId) {
            return $job->model->id === $firewallRuleId;
        });
    }
}
