<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GetTest extends TestCase
{
    use DatabaseMigrations;

    protected Network $network;
    protected NetworkPolicy $networkPolicy;
    protected NetworkRule $networkRule;

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
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->networkRule = factory(NetworkRule::class)->create([
            'id' => 'nr-test',
            'network_policy_id' => $this->networkPolicy->id,
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
        ]);
    }

    public function testGetCollection()
    {
        $this->get(
            '/v2/network-rules',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'nr-test',
            'network_policy_id' => 'np-test',
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
        ])->assertResponseStatus(200);
    }

    public function testGetResource()
    {
        $this->get(
            '/v2/network-acl-rules/nr-test',
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.read',
            ]
        )->seeJson([
            'id' => 'nr-test',
            'network_policy_id' => 'np-test',
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
        ])->assertResponseStatus(200);
    }
}
