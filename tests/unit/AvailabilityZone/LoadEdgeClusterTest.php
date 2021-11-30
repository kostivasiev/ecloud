<?php

namespace Tests\unit\AvailabilityZone;

use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class LoadEdgeClusterTest extends TestCase
{
    public function testLoadsDefaultEdgeCluster()
    {
        $this->nsxServiceMock()->expects('get')
            ->times(3)
            ->withArgs([
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . config('defaults.tag.networking.default')
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => 'STANDARD-EDGE-CLUSTER-ID'
                        ]
                    ]
                ]));
            });

        $this->assertEquals('STANDARD-EDGE-CLUSTER-ID', $this->availabilityZone()->getNsxEdgeClusterId());

        $this->assertEquals('STANDARD-EDGE-CLUSTER-ID', $this->availabilityZone()->getNsxEdgeClusterId(false));

        $this->vpc()->setAttribute('advanced_networking', false)->save();

        $this->assertEquals(
            'STANDARD-EDGE-CLUSTER-ID',
            $this->availabilityZone()->getNsxEdgeClusterId($this->vpc()->advanced_networking)
        );
    }

    public function testLoadsAdvancedNetworkingEdgeCluster()
    {
        $this->nsxServiceMock()->expects('get')
            ->twice()
            ->withArgs([
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . config('defaults.tag.networking.advanced')
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => 'ADVANCED-NETWORKING-EDGE-CLUSTER-ID'
                        ]
                    ]
                ]));
            });

        $this->assertEquals('ADVANCED-NETWORKING-EDGE-CLUSTER-ID', $this->availabilityZone()->getNsxEdgeClusterId(true));

        $this->vpc()->setAttribute('advanced_networking', true)->save();

        $this->assertEquals(
            'ADVANCED-NETWORKING-EDGE-CLUSTER-ID',
            $this->availabilityZone()->getNsxEdgeClusterId($this->vpc()->advanced_networking)
        );
    }

    public function testLoadsMultipleEdgeNodesFails()
    {
        $this->expectExceptionMessage(
            'Failed to determine standard edge cluster ID for availability zone ' . $this->availabilityZone()->id
        );

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . config('defaults.tag.networking.default')
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 2,
                    'results' => [
                        [
                            'id' => 'STANDARD-EDGE-CLUSTER-ID'
                        ],
                        [
                            'id' => 'ADVANCED-NETWORKING-EDGE-CLUSTER-ID'
                        ]
                    ]
                ]));
            });

        $this->availabilityZone()->getNsxEdgeClusterId();

        $this->expectExceptionMessage(
            'Failed to determine advanced networking edge cluster ID for availability zone ' . $this->availabilityZone()->id
        );
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . config('defaults.tag.networking.advanced')
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 2,
                    'results' => [
                        [
                            'id' => 'STANDARD-EDGE-CLUSTER-ID'
                        ],
                        [
                            'id' => 'ADVANCED-NETWORKING-EDGE-CLUSTER-ID'
                        ]
                    ]
                ]));
            });

        $this->availabilityZone()->getNsxEdgeClusterId();
    }

    public function testLoadsNoEdgeNodesFails()
    {
        $this->expectExceptionMessage(
            'Failed to determine standard edge cluster ID for availability zone ' . $this->availabilityZone()->id
        );

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . config('defaults.tag.networking.default')
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                    'results' => []
                ]));
            });

        $this->availabilityZone()->getNsxEdgeClusterId();
    }
}
