<?php
namespace Tests\V2\NetworkRule;

use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpc();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);

        $this->nsxServiceMock()->shouldReceive('get')
            ->withSomeOfArgs('policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-abc123xyz')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(
                    [
                        'publish_status' => 'REALIZED'
                    ]
                ));
            });
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-abc123xyz')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-abc123xyz')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });

        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-abc123xyz',
            'network_id' => $this->network->id,
        ]);
    }

    public function testCreateResource()
    {
        $data = [
            'network_policy_id' => 'np-abc123xyz',
            'sequence' => 1,
            'source' => '10.0.1.0/32',
            'destination' => '10.0.2.0/32',
            'action' => 'ALLOW',
            'enabled' => true,
        ];
        $this->post(
            '/v2/network-rules',
            $data,
            [
                'X-consumer-custom-id' => '0-0',
                'X-consumer-groups' => 'ecloud.write',
            ]
        )->seeInDatabase(
            'network_rules',
            $data,
            'ecloud'
        )->assertResponseStatus(201);
    }
}
