<?php
namespace Tests\V2\NetworkAclRulePort;

use App\Models\V2\NetworkAcl;
use App\Models\V2\Network;
use App\Models\V2\NetworkAclRule;
use App\Models\V2\NetworkAclRulePort;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected Network $network;
    protected NetworkAcl $networkAcl;
    protected NetworkAclRule $networkAclRule;
    protected NetworkAclRulePort $networkAclRulePort;

    public function setUp(): void
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

    public function testDeleteResource()
    {
        $this->delete(
            '/v2/network-acl-rule-ports/narp-abc123xyz',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);
        $aclRulePort = NetworkAclRulePort::withTrashed()->findOrFail($this->networkAclRulePort->id);
        $this->assertNotNull($aclRulePort->deleted_at);
    }
}