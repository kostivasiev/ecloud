<?php

namespace Tests\V2\FirewallRule;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\FirewallPolicy;
use App\Models\V2\Region;
use App\Models\V2\Router;
use App\Models\V2\Vpc;
use Faker\Factory as Faker;
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
            'region_id' => $this->region->getKey(),
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->router = factory(Router::class)->create([
            'vpc_id' => $this->vpc->getKey()
        ]);
        $this->firewall_policy = factory(FirewallPolicy::class)->create([
            'router_id' => $this->router->getKey(),
        ]);
    }

    public function testValidDataSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
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
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(201);
    }

    public function testSourceANYSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => 'ANY',
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
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => 'ANY',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(201);
    }

    public function testDestinationANYSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => '212.22.18.10/24',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->seeInDatabase('firewall_rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => '212.22.18.10/24',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(201);
    }

    public function testMissingSourceFails()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => '',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(422);
    }

    public function testMissingDestinationFails()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewall_policy->getKey(),
            'source' => '212.22.18.10/24',
            'destination' => '',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], [
            'X-consumer-custom-id' => '0-0',
            'X-consumer-groups' => 'ecloud.write',
        ])->assertResponseStatus(422);
    }
}
