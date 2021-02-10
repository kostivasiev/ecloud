<?php
namespace Tests\V2\NetworkAclPolicy;

use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

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
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-acls',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => $this->aclPolicy->id,
            'network_id' => $this->aclPolicy->network_id,
            'vpc_id' => $this->aclPolicy->vpc_id,
            'name' => $this->aclPolicy->name,
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-acls/'.$this->aclPolicy->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => $this->aclPolicy->id,
            'network_id' => $this->aclPolicy->network_id,
            'vpc_id' => $this->aclPolicy->vpc_id,
            'name' => $this->aclPolicy->name,
        ])->assertResponseStatus(200);
    }
}