<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Models\V2\NetworkRule;
use GuzzleHttp\Psr7\Response;
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

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-abc123xyz')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-abc123xyz',
            'network_id' => $this->network->id,
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