<?php
namespace Tests\V2\NetworkAclRule;

use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkAclRule;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAclRule $aclRule;
    protected NetworkAclPolicy $aclPolicy;
    protected Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc();
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

    public function testUpdateResource()
    {
        $newAclPolicy = factory(NetworkAclPolicy::class)->create([
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->patch(
            '/v2/network-acl-rules/'.$this->aclRule->id,
            [
                'network_acl_policy_id' => $newAclPolicy->id,
                'action' => 'REJECT',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);
        $this->aclRule->refresh();
        $this->assertEquals($newAclPolicy->id, $this->aclRule->network_acl_policy_id);
        $this->assertEquals('REJECT', $this->aclRule->action);
    }
}