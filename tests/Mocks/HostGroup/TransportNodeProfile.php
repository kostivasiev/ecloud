<?php

namespace Tests\Mocks\HostGroup;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

trait TransportNodeProfile
{
    public function logStarted()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Started') !== false;
            });
    }

    public function logSkipped()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Skipped') !== false;
            });
    }

    public function logFinished()
    {
        Log::shouldReceive('info')
            ->once()
            ->withArgs(function ($message) {
                return strpos($message, 'Finished') !== false;
            });
    }

    public function transportNodeNoProfiles()
    {
        $this->logStarted();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
    }

    public function transportNodeNameExists(string $name)
    {
        $this->logStarted();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () use ($name) {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'display_name' => $name,
                        ]
                    ]
                ]));
            });
        $this->logSkipped();
    }

    public function validTransportNodeProfile()
    {
        $this->logStarted();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/transport-node-profiles')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });
    }

    public function networkSwitchNoResults()
    {
        $this->validTransportNodeProfile();
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
    }

    public function validNetworkSwitch()
    {
        $this->validTransportNodeProfile();
        $this->kingpinServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v2/vpc/' . $this->vpc()->id . '/network/switch')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'uuid' => 'b4d001c8-f3a9-47b9-b904-78ce9fd6c4d6',
                ]));
            });
    }

    public function transportZonesNoResults()
    {
        $this->validNetworkSwitch();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
    }

    public function validTransportZones()
    {
        $this->validNetworkSwitch();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:TransportZone%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-overlay-tz')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '42d981bc-22a0-42fb-b815-42edaf06b5f9',
                            'transport_zone_profile_ids' => [
                                'ec94aaa1-a50d-4eb8-8fa9-128946efc76a',
                                '7ed25dd0-a403-44bd-95a8-95d71b266526',
                            ]
                        ]
                    ]
                ]));
            });
    }

    public function uplinkHostNoResults()
    {
        $this->validTransportZones();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
            ->andReturnUsing(function () {
                return new Response(200, [], '');
            });
    }

    public function validUplinkHost()
    {
        $this->validTransportZones();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/search/query?query=resource_type:UplinkHostSwitchProfile%20AND%20tags.scope:ukfast%20AND%20tags.tag:default-uplink-profile')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => '6b3c18e8-2c21-4dd3-bb13-8c589ce2fd85'
                        ]
                    ]
                ]));
            });
        $this->logFinished();
    }

    protected function getThrownException(int $code, string $message, string $method = 'get')
    {
        $thrownException = new RequestException($message, new Request($method, '', []), new Response($code));
        if ($code === 500) {
            $thrownException = new ServerException($message, new Request($method, '', []), new Response($code));
        }
        return $thrownException;
    }

    public function noComputeCollectionItem(int $code, string $message)
    {
        $thrownException = $this->getThrownException($code, $message);
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $this->hostGroup->id)
            ->andThrow($thrownException);
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'Compute Collection for HostGroup') !== false;
        }));
        if ($code == 404) {
            // The job should not fail
            $this->job->shouldNotReceive('fail');
        }
    }

    public function validComputeCollection()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $this->hostGroup->id)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'external_id' => 'd54d92a4-0d03-49c9-b621-fbbdd7f3e422',
                        ]
                    ]
                ]));
            });
    }

    public function noTransportNodeCollectionItem(int $code, string $message)
    {
        $thrownException = $this->getThrownException($code, $message);
        $this->validComputeCollection();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections?compute_collection_id=d54d92a4-0d03-49c9-b621-fbbdd7f3e422'
            )->andThrow($thrownException);
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'TransportNode Collection for HostGroup') !== false;
        }));
    }

    public function validTransportNodeCollection()
    {
        $this->validComputeCollection();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections?compute_collection_id=d54d92a4-0d03-49c9-b621-fbbdd7f3e422'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 1,
                        ]
                    ]
                ]));
            });
    }

    public function detachNodeFail(int $code, string $message)
    {
        $thrownException = $this->getThrownException($code, $message, 'delete');
        $this->validTransportNodeCollection();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/1')
            ->andThrow($thrownException);
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'Failed to detach transport node profile for Host Group') !== false;
        }));
    }

    public function detachNodeSuccess()
    {
        $this->validTransportNodeCollection();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/1')
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }

    public function deleteNodeFail(int $code, string $message)
    {
        $thrownException = $this->getThrownException($code, $message, 'delete');
        $this->detachNodeSuccess();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/1')
            ->andThrow($thrownException);
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->with(\Mockery::on(function ($arg) {
            return stripos($arg, 'Failed to delete transport node profile for Host Group') !== false;
        }));
    }

    public function deleteNodeSuccessful()
    {
        $this->detachNodeSuccess();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/1')
            ->andReturnUsing(function () {
                return new Response(200);
            });
    }
}