<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected \Faker\Generator $faker;
    protected FirewallPolicy $firewallPolicy;
    protected FirewallRule $firewallRule;
    protected Region $region;
    protected Router $router;
    protected Vpc $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->faker = Faker::create();

        $this->region = factory(Region::class)->create();
        factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->firewallPolicy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->id,
        ])->first();
        $this->firewallRule = factory(FirewallRule::class)->create([
            'name' => 'Demo firewall rule 1',
            'firewall_policy_id' => $this->firewallPolicy->getKey(),
            'service_type' => 'TCP',
            'source' => '192.168.100.1',
            'source_ports' => '80,443',
            'destination' => '212.22.18.10',
            'destination_ports' => '8080,4043',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ])->first();
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/firewall-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
            ])
            ->assertResponseStatus(200);
    }

    public function testGetItemDetail()
    {
        $this->get(
            '/v2/firewall-rules/' . $this->firewallRule->getKey(),
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )
            ->seeJson([
                'id' => $this->firewallRule->id,
                'name' => $this->firewallRule->name,
            ])
            ->assertResponseStatus(200);
    }

}
