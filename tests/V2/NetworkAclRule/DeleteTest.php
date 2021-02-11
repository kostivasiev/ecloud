<?php
namespace Tests\V2\NetworkAclRule;

use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkAclRule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAclRule $aclRule;
    protected NetworkAclPolicy $aclPolicy;
    protected Network $network;

    public function setUp(): void
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

    public function testDeleteResource()
    {
        $this->delete(
            '/v2/network-acl-rules/'.$this->aclRule->id,
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);
        $aclRule = NetworkAclRule::withTrashed()->findOrFail($this->aclRule->id);
        $this->assertNotNull($aclRule->deleted_at);
    }
}