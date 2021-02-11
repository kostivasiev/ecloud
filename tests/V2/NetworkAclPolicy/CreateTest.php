<?php
namespace Tests\V2\NetworkAclPolicy;

use App\Models\V2\NetworkAclPolicy;
use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->vpc();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'id' => 'net-test',
            'router_id' => $this->router()->id,
        ]);
    }

    public function testCreateResource()
    {
        $data = [
            'name' => 'Test ACL',
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ];
        $this->post(
            '/v2/network-acl-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_acl_policies',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        $data = [
            'name' => 'Test ACL',
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ];
        factory(NetworkAclPolicy::class)->create($data);
        $this->post(
            '/v2/network-acl-policies',
            $data,
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