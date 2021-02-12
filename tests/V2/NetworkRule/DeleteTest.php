<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkRule;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeleteTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkRule $networkRule;
    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpc();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'id' => 'net-abc123xyz',
            'router_id' => $this->router()->id,
        ]);
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-abc123xyz',
            'network_id' => $this->network->id,
            'vpc_id' => $this->vpc()->id,
        ]);
        $this->networkRule = factory(NetworkRule::class)->create([
            'id' => 'nr-abc123xyz',
            'network_policy_id' => $this->networkPolicy->id,
        ]);
    }

    public function testDeleteResource()
    {
        $this->delete(
            '/v2/network-rules/nr-abc123xyz',
            [],
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->assertResponseStatus(204);
        $networkRule = NetworkRule::withTrashed()->findOrFail($this->networkRule->id);
        $this->assertNotNull($networkRule->deleted_at);
    }
}