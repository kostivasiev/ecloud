<?php
namespace Tests\V2\NetworkAclRulePort;

use App\Models\V2\Network;
use App\Models\V2\NetworkAcl;
use App\Models\V2\NetworkAclRule;
use App\Models\V2\NetworkAclRulePort;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected Network $network;
    protected NetworkAcl $networkAcl;
    protected NetworkAclRule $networkAclRule;
    protected NetworkAclRulePort $networkAclRulePort;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->networkAcl = factory(NetworkAcl::class)->create([
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->networkAclRule = factory(NetworkAclRule::class)->create([
            'id' => 'nar-abc123xyz',
            'network_acl_id' => $this->networkAcl->id,
        ]);
        $this->networkAclRulePort = factory(NetworkAclRulePort::class)->create([
            'id' => 'narp-abc123xyz',
            'network_acl_rule_id' => $this->networkAclRule->id,
        ]);
    }

    public function testCreateResource()
    {
        $data = [
            'network_acl_rule_id' => 'nar-abc123xyz',
            'protocol' => 'TCP',
            'source' => '443',
            'destination' => '555',
        ];
        $this->post(
            '/v2/network-acl-rule-ports',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_acl_rule_ports',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }
}
