<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    protected function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
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
            '/v2/network-policies/np-test',
            [
                'network_id' => 'net-new',
                'vpc_id' => 'vpc-new',
            ],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_policies',
            [
                'id' => 'np-test',
                'network_id' => 'net-new',
                'vpc_id' => 'vpc-new',
            ],
            'ecloud'
        )->assertResponseStatus(200);
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
        factory(NetworkPolicy::class)->create([
            'network_id' => $newNetwork->id,
            'vpc_id' => $newVpc->id,
        ]);
        $this->patch(
            '/v2/network-policies/np-test',
            [
                'network_id' => 'net-111aaa222',
                'vpc_id' => 'vpc-new',
            ],
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