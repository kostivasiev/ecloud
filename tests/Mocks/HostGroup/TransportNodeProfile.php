<?php

namespace Tests\Mocks\HostGroup;

use GuzzleHttp\Exception\RequestException;
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

    public function noComputeCollectionItem()
    {
        $this->logStarted();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/api/v1/fabric/compute-collections?origin_type=VC_Cluster&display_name=' . $this->hostGroup->id)
            ->andThrow(new RequestException('Not Found', new Request('get', '', []), new Response(404)));
        Log::shouldReceive('warning')
            ->withSomeOfArgs(get_class($this->job) . ' : Compute Collection for HostGroup hg-test could not be retrieved, skipping.');
    }

    public function validComputeCollection()
    {
        $this->logStarted();
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

    public function noTransportNodeCollectionItem()
    {
        $this->validComputeCollection();
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs(
                '/api/v1/transport-node-collections?compute_collection_id=d54d92a4-0d03-49c9-b621-fbbdd7f3e422'
            )->andThrow(
                new RequestException(
                    'Not Found',
                    new Request('get', '', []),
                    new Response(404)
                )
            );
        Log::shouldReceive('warning')
            ->withSomeOfArgs(
                get_class($this->job) . ' : TransportNode Collection for HostGroup hg-test could not be retrieved, skipping.'
            );
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

    public function detachNodeFail()
    {
        $this->validTransportNodeCollection();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-collections/1')
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));
        Log::shouldReceive('warning')
            ->withSomeOfArgs(
                get_class($this->job) . ' : Failed to detach transport node profile for Host Group hg-test, skipping'
            );
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

    public function deleteNodeFail()
    {
        $this->detachNodeSuccess();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/1')
            ->andThrow(new RequestException('Not Found', new Request('delete', '', []), new Response(404)));
        Log::shouldReceive('warning')
            ->withSomeOfArgs(
                get_class($this->job) . ' : Failed to delete transport node profile for Host Group hg-test, skipping.'
            );
    }

    public function deleteNodeSuccessful()
    {
        $this->detachNodeSuccess();
        $this->nsxServiceMock()->expects('delete')
            ->withSomeOfArgs('/api/v1/transport-node-profiles/1')
            ->andReturnUsing(function () {
                return new Response(200);
            });
        $this->logFinished();
    }
}