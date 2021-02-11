<?php
namespace Tests\V2\NetworkAclPolicy;

use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAclPolicy $aclPolicy;
    protected Network $network;

    protected function setUp(): void
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
    }

    public function testUpdateResource()
    {
        $newNetwork = factory(Network::class)->create([
            'id' => 'net-new',
            'router_id' => $this->router()->id,
        ]);
        $newVpc = factory(Vpc::class)->create([
            'id' => 'vpc-new',
            'region_id' => $this->region()->id
        ]);
        $this->patch(
            '/v2/network-acl-policies/'.$this->aclPolicy->id,
            [
                'network_id' => $newNetwork->id,
                'vpc_id' => $newVpc->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);
        $this->aclPolicy->refresh();
        $this->assertEquals($this->aclPolicy->network_id, $newNetwork->id);
        $this->assertEquals($this->aclPolicy->vpc_id, $newVpc->id);
    }

    public function testUpdateResourceNetworkHasAcl()
    {
        $newNetwork = factory(Network::class)->create();
        $newVpc = factory(Vpc::class)->create([
            'id' => 'vpc-new',
            'region_id' => $this->region()->id
        ]);
        factory(NetworkAclPolicy::class)->create([
            'network_id' => $newNetwork->id,
            'vpc_id' => $newVpc->id,
        ]);
        $this->patch(
            '/v2/network-acl-policies/'.$this->aclPolicy->id,
            [
                'network_id' => $newNetwork->id,
                'vpc_id' => $newVpc->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'This network id already has an assigned ACL'
        ])->assertResponseStatus(422);
    }
}