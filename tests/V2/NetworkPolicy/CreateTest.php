<?php
namespace Tests\V2\NetworkPolicy;

use App\Models\V2\NetworkPolicy;
use App\Models\V2\Network;
use GuzzleHttp\Psr7\Response;
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

        // bind data so we can use actual NSX mocks
        app()->bind(NetworkPolicy::class, function () {
            return factory(NetworkPolicy::class)->make([
                'id' => 'np-abc123xyz',
                'network_id' => 'net-test',
                'name' => 'Test Policy',
            ]);
        });

        $mockIds = ['np-abc123xyz', 'np-zzzxxxyyy'];
        foreach ($mockIds as $mockId) {
            $this->nsxServiceMock()->shouldReceive('patch')
                ->withSomeOfArgs('/policy/api/v1/infra/domains/default/security-policies/'.$mockId)
                ->andReturnUsing(function () {
                    return new Response(200, [], '');
                });
        }

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
            ],
            'ecloud'
        )->assertResponseStatus(201);
    }

    public function testCreateResourceNetworkAlreadyAssigned()
    {
        $data = [
            'name' => 'Test Policy',
            'network_id' => $this->network->id,
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