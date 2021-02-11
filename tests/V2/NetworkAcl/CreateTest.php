<?php
namespace Tests\V2\NetworkAcl;

use App\Models\V2\NetworkAcl;
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
            '/v2/network-acls',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_acls',
            [
                'name' => 'Test ACL',
                'network_id' => $this->network->id,
                'vpc_id' => $this->vpc()->id,
            ],
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
        factory(NetworkAcl::class)->create($data);
        $this->post(
            '/v2/network-acls',
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