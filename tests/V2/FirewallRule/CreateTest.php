<?php

namespace Tests\V2\FirewallRule;

use App\Events\V2\FirewallPolicy\Saved as FirewallPolicySaved;
use App\Events\V2\FirewallRule\Saved as FirewallRuleSaved;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityZone();

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('patch')
            ->andReturn(
                new Response(200, [], ''),
            );

        // TODO - Replace with real mock
        $this->nsxServiceMock()->shouldReceive('get')
            ->andReturn(
                new Response(200, [], json_encode(['publish_status' => 'REALIZED']))
            );
    }

    public function testValidDataSucceeds()
    {
        $this->post('/v2/firewall-rules', [
            'name' => 'Demo firewall rule 1',
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
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
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => '192.168.100.1/24',
            'destination' => '212.22.18.10/24',
            'action' => 'ALLOW',
            'direction' => 'IN',
            'enabled' => true
        ], 'ecloud')->assertResponseStatus(201);

        $firewallRuleId = (json_decode($this->response->getContent()))->data->id;

        Event::assertDispatched(FirewallPolicySaved::class, function ($job) {
            return $job->model->id === $this->firewallPolicy()->id;
        });

        Event::assertDispatched(FirewallRuleSaved::class, function ($job) use ($firewallRuleId) {
            return $job->model->id === $firewallRuleId;
        });
    }
}
