<?php
namespace Tests\V2\NetworkAcl;

use App\Models\V2\NetworkAcl;
use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkAcl $networkAcl;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'id' => 'net-test',
            'router_id' => $this->router()->id,
        ]);
        $this->networkAcl = factory(NetworkAcl::class)->create([
            'id' => 'na-test',
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
            'id' => 'na-test',
            'network_id' => 'net-test',
            'vpc_id' => 'vpc-test',
            'name' => 'na-test',
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-acls/'.$this->networkAcl->id,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'na-test',
            'network_id' => 'net-test',
            'vpc_id' => 'vpc-test',
            'name' => 'na-test',
        ])->assertResponseStatus(200);
    }
}