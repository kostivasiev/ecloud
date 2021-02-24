<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use GuzzleHttp\Psr7\Response;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UpdateTest extends TestCase
{
    use DatabaseMigrations;

    protected NetworkPolicy $networkPolicy;
    protected Network $network;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();
        $this->network = factory(Network::class)->create([
            'router_id' => $this->router()->id,
        ]);

        $mockIds = ['np-test', 'np-zzzxxxyyy'];
        foreach ($mockIds as $mockId) {
            $this->nsxServiceMock()->shouldReceive('patch')
                ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/' . $mockId)
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
            $this->nsxServiceMock()->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/' . $mockId])
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
                });
            $this->nsxServiceMock()->shouldReceive('patch')
                ->withSomeOfArgs('/policy/api/v1/infra/domains/default/groups/' . $mockId)
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
            $this->nsxServiceMock()->shouldReceive('get')
                ->withArgs(['policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/groups/' . $mockId])
                ->andReturnUsing(function () {
                    return new Response(200, [], json_encode(['publish_status' => 'REALIZED']));
                });
        }

        $this->networkPolicy = factory(NetworkPolicy::class)->create([
            'id' => 'np-test',
            'network_id' => $this->network->id,
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

    public function testUpdateResourceNetworkHasAcl()
    {
        $newNetwork = factory(Network::class)->create([
            'id' => 'net-111aaa222',
            'router_id' => $this->router()->id,
        ]);
        $newVpc = factory(Vpc::class)->create([
            'id' => 'vpc-new',
            'region_id' => $this->region()->id
        ]);
        factory(NetworkPolicy::class)->create([
            'id' => 'np-zzzxxxyyy',
            'network_id' => $newNetwork->id,
        ]);
        $this->patch(
            '/v2/network-policies/np-test',
            [
                'network_id' => 'net-111aaa222',
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