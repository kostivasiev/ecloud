<?php
namespace Tests\V2\NetworkAclRule;

use App\Models\V2\Network;
use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\NetworkAclRule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAclRule $aclRule;
    protected NetworkAclPolicy $aclPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->aclPolicy = factory(NetworkAclPolicy::class)->create([
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->aclRule = factory(NetworkAclRule::class)->create([
            'id' => 'nar-abc123xyz',
            'network_acl_policy_id' => $this->aclPolicy->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-acl-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => $this->aclRule->id,
            'network_acl_policy_id' => $this->aclRule->network_acl_policy_id,
            'sequence' => $this->aclRule->sequence,
            'source' => $this->aclRule->source,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-acl-rules/'.$this->aclRule->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => $this->aclRule->id,
            'network_acl_policy_id' => $this->aclRule->network_acl_policy_id,
            'sequence' => $this->aclRule->sequence,
            'source' => $this->aclRule->source,
        ])->assertResponseStatus(200);
    }
}
