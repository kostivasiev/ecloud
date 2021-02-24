<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class CreateTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
        $this->network();

        // bind data so we can use actual NSX mocks
        app()->bind(NetworkPolicy::class, function () {
            return factory(NetworkPolicy::class)->make([
                'id' => 'np-abc123xyz',
                'network_id' => $this->network()->id,
                'name' => 'Test Policy',
            ]);
        });

        $mockNetworkPolicyIds = ['np-abc123xyz', 'np-zzzxxxyyy'];
        foreach ($mockNetworkPolicyIds as $mockNetworkPolicyId) {
            $this->nsxServiceMock()->shouldReceive('patch')
                ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/' . $mockNetworkPolicyId)
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
            $this->nsxServiceMock()->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/' . $mockNetworkPolicyId])
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
                });
            $this->nsxServiceMock()->shouldReceive('patch')
                ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/' . $mockNetworkPolicyId)
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
            $this->nsxServiceMock()->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/' . $mockNetworkPolicyId])
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
                });
        }
    }

    public function testCreateResource()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
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
                'network_id' => $this->network()->id,
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network()->id,
        ];
        factory(NetworkPolicy::class)->create(array_merge(['id' => 'np-zzzxxxyyy'], $data));
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