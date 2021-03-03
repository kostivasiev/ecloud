<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'id' => 'net-test',
            'router_id' => $this->router()->id,
        ]);
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network->id,
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-policies',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'np-test',
            'network_id' => 'net-test',
            'name' => 'np-test',
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-policies/np-test',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'np-test',
            'network_id' => 'net-test',
            'name' => 'np-test',
        ])->assertResponseStatus(200);
    }
}