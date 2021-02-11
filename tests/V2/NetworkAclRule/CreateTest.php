<?php
namespace Tests\V2\NetworkAclRule;

use App\Models\V2\Network;
use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\NetworkAclRule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

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
    }

    public function testCreateResource()
    {
        $data = [
            'network_acl_policy_id' => $this->aclPolicy->id,
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
        ];
        $this->post(
            '/v2/network-acl-rules',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(201);

        $aclRuleId = (json_decode($this->response->getContent()))->data->id;
        $aclRule = NetworkAclRule::findOrFail($aclRuleId);
        $this->assertEquals($data['network_acl_policy_id'], $aclRule->network_acl_policy_id);
        $this->assertEquals($data['sequence'], $aclRule->sequence);
        $this->assertEquals($data['source'], $aclRule->source);
    }
}
