<?php
namespace Tests\unit\Jobs\OrchestratorBuild\Mocks;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

trait CreateHostGroupsMocks
{
    public function buildHostgroupIsCreatedMocks()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->with('/api/v2/vpc/vpc-test/hostgroup/hg-test')
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs('/api/v2/vpc/vpc-test/hostgroup')
            ->andReturnUsing(function () {
                return new Response(201);
            });
        $this->validVtepIpPool();
        $this->nsxServiceMock()->expects('post')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-collections')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode(['results' => [['display_name' => 'dummy name']]]));
            });
        $this->nsxServiceMock()->expects('get')
            ->with('/api/v1/search/query?query=resource_type:TransportNodeProfile%20AND%20display_name:tnp-hg-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'TEST-TRANSPORT-NODE-COLLECTION-ID',
                        ],
                    ],
                ]));
            });
        $this->nsxServiceMock()->expects('get')
            ->with('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=hg-test')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'external_id' => 'TEST-COMPUTE-COLLECTION-ID',
                        ],
                    ],
                ]));
            });
        $this->nsxServiceMock()->expects('post')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections',
                [
                    'json' => [
                        'resource_type' => 'TransportNodeCollection',
                        'display_name' => 'tnc-hg-test',
                        'description' => 'API created Transport Node Collection',
                        'compute_collection_id' => 'TEST-COMPUTE-COLLECTION-ID',
                        'transport_node_profile_id' => 'TEST-TRANSPORT-NODE-COLLECTION-ID',
                    ]
                ]
            )
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }
}