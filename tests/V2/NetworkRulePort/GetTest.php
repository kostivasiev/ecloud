<?php
namespace Tests\V2\NetworkRulePort;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected Network $network;
    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;
    protected NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpc();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'id' => 'net-test',
            'router_id' => $this->router()->id,
        ]);
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => 'net-test',
        ]);
        $this->networkRule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => 'np-test',
        ]);
        $this->networkRulePort = factory(NetworkRulePort::class)->create([
            'id' => 'nrp-test',
            'network_rule_id' => 'nr-test',
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-rule-ports',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'nrp-test',
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-rule-ports/nrp-test',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'nrp-test',
            'network_rule_id' => 'nr-test',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ])->assertResponseStatus(200);
    }
}
