<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
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
            'name' => 'Test Policy',
            'network_id' => 'net-test',
            'vpc_id' => $this->vpc()->id,
        ];
        $this->post(
            '/v2/network-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'name' => 'Test Policy',
                'network_id' => 'net-test',
                'vpc_id' => 'vpc-test',
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ];
        factory(NetworkPolicy::class)->create($data);
        $this->post(
            '/v2/network-policies',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeJson([
            'title' => 'Validation Error',
            'detail' => 'This network id already has an assigned Policy'
        ])->assertResponseStatus(422);
    }
}