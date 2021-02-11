<?php
namespace Tests\V2\NetworkAcl;

use App\Models\V2\NetworkAcl;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAcl $networkAcl;
    protected Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->networkAcl = factory(NetworkAcl::class)->create([
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
            '/v2/network-acls/'.$this->networkAcl->id,
            [
                'network_id' => $newNetwork->id,
                'vpc_id' => $newVpc->id,
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(200);
        $this->networkAcl->refresh();
        $this->assertEquals($this->networkAcl->network_id, $newNetwork->id);
        $this->assertEquals($this->networkAcl->vpc_id, $newVpc->id);
    }

    public function testUpdateResourceNetworkHasAcl()
    {
        $newNetwork = factory(Network::class)->create([
            'id' => 'net-111aaa222',
        ]);
        $newVpc = factory(Vpc::class)->create([
            'id' => 'vpc-new',
            'region_id' => $this->region()->id
        ]);
        factory(NetworkAcl::class)->create([
            'network_id' => $newNetwork->id,
            'vpc_id' => $newVpc->id,
        ]);
        $this->patch(
            '/v2/network-acls/'.$this->networkAcl->id,
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