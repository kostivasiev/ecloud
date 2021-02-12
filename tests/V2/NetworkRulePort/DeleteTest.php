<?php
namespace Tests\V2\NetworkRulePort;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
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
            'vpc_id' => 'vpc-test',
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

    public function testDeleteResource()
    {
        $this->delete(
            '/v2/network-acl-rule-ports/nrp-test',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);
        $networkRulePort = NetworkRulePort::withTrashed()->findOrFail('nrp-test');
        $this->assertNotNull($networkRulePort->deleted_at);
    }
}