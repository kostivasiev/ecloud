<?php

namespace Tests\Mocks\HostGroup;

use App\Jobs\Nsx\HostGroup\DeleteTransportNodeProfile;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

trait DeleteTransportNodeProfileJob
{
    protected $deleteTransportNode;

    public function transportNodeSetup()
    {
        $this->deleteTransportNode = \Mockery::mock(DeleteTransportNodeProfile::class)->makePartial();
        $this->deleteTransportNode->model = $this->hostGroup();
        $this->logStarted();
    }

    public function logStarted()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Started') !== false;
            });
    }

    public function computeCollectionItem()
    {
        $this->nsxServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v1/fabric/compute-collections' .
                '?origin_type=VC_Cluster&display_name=' . $this->hostGroup()->id
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'external_id' => 'e8040fd9-c4d2-4435-a1c8-0d8ee6b2fc84:domain-c57728',
                        ]
                    ]
                ]));
            });
    }

    public function computeCollectionItemNull()
    {
        $this->nsxServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v1/fabric/compute-collections' .
                '?origin_type=VC_Cluster&display_name=' . $this->hostGroup()->id
            )->andReturnNull();
        $this->deleteTransportNode->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return $argument->getMessage() === 'Failed to get ComputeCollection item';
            }));
    }

    public function transportNodeCollection()
    {
        $this->computeCollectionItem();
        $this->nsxServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections'.
                '?compute_collection_id=e8040fd9-c4d2-4435-a1c8-0d8ee6b2fc84:domain-c57728'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    "results" => [
                        [
                            "id" => "8d43b1a7-b210-496b-b93f-3e091968de9c"
                        ]
                    ]
                ]));
            });
    }

    public function transportNodeCollectionNull()
    {
        $this->computeCollectionItem();
        $this->nsxServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections'.
                '?compute_collection_id=e8040fd9-c4d2-4435-a1c8-0d8ee6b2fc84:domain-c57728'
            )->andReturnNull();
        $this->deleteTransportNode->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return $argument->getMessage() === 'Failed to get TransportNode item';
            }));
    }

    public function detachNodeFail()
    {
        $this->transportNodeCollection();
        $this->nsxServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/8d43b1a7-b210-496b-b93f-3e091968de9c')
            ->andReturnUsing(function () {
                return new Response(404);
            });
        $this->logDebug();
        $this->deleteTransportNode->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return strpos($argument->getMessage(), 'Failed to detach') !== false;
            }));
    }

    public function detachNodeSuccess()
    {
        $this->transportNodeCollection();
        $this->nsxServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/8d43b1a7-b210-496b-b93f-3e091968de9c')
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    public function deleteNodeFail()
    {
        $this->detachNodeSuccess();
        $this->nsxServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/8d43b1a7-b210-496b-b93f-3e091968de9c')
            ->andReturnUsing(function () {
                return new Response(404);
            });
        $this->logDebug();
        $this->deleteTransportNode->expects('fail')
            ->with(\Mockery::on(function ($argument) {
                return strpos($argument->getMessage(), 'Failed to delete') !== false;
            }));
    }

    public function deleteNodeSuccess()
    {
        $this->detachNodeSuccess();
        $this->nsxServiceMock()
            ->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/8d43b1a7-b210-496b-b93f-3e091968de9c')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->logFinished();
    }

    public function logDebug()
    {
        Log::shouldReceive('debug')
            ->once()
            ->withAnyArgs();
    }

    public function logFinished()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Finished') !== false;
            });
    }
}