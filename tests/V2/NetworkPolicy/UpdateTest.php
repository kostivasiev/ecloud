<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    public function testUpdateResource()
    {
        $this->nsxServiceMock()->expects('patch')->twice()
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')->twice()
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });
        $this->nsxServiceMock()->expects('patch')->twice()
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')->twice()
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        $networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);

        $newNetwork = factory(Network::class)->create([
            'id' => 'net-new',
            'router_id' => $this->router()->id,
        ]);

        $this->patch(
            '/v2/network-policies/' . $networkPolicy->id,
            [
                'network_id' => $newNetwork->id,
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
            ],
            'ecloud'
        )->assertResponseStatus(200);
    }

    public function testUpdateResourceNetworkHasPolicy()
    {
        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });
        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-test')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-test'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network()->id,
        ]);

        $newNetwork = factory(Network::class)->create([
            'id' => 'net-111aaa222',
            'router_id' => $this->router()->id,
        ]);

        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/np-zzzxxxyyy')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/np-zzzxxxyyy'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });
        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/np-zzzxxxyyy')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/np-zzzxxxyyy'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
            });

        factory(NetworkPolicy::class)->create([
            'id' => 'np-zzzxxxyyy',
            'network_id' => $newNetwork->id,
        ]);

        $this->patch(
            '/v2/network-policies/np-test',
            [
                'network_id' => $newNetwork->id,
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