<?php

namespace Tests\unit\Console\Commands\Router;

use App\Console\Commands\Router\FixEdgeClusters;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Tests\TestCase;

class FixEdgeClustersTest extends TestCase
{
    protected $mock;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock = \Mockery::mock(FixEdgeClusters::class)->makePartial();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
    }

    public function testGetT0TagNoVpc()
    {
        Model::withoutEvents(function () {
            $this->vpc()->delete();
        });

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getT0Tag($this->router()));
        $this->assertEquals($this->router()->id . ' : VPC `' . $this->vpc()->id . '` not found', $message);
    }

    public function testGetT0Tag()
    {
        // Advanced Management tag
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $this->assertEquals('az-adminadv', $this->mock->getT0Tag($this->router()));

        // Standard Management tag
        $this->router()->vpc->setAttribute('advanced_networking', false)->saveQuietly();
        $this->assertEquals('az-admin', $this->mock->getT0Tag($this->router()));
    }

    public function testGetEdgeClusterUuidNoAvailabilityZone()
    {
        Model::withoutEvents(function () {
            $this->availabilityZone()->delete();
        });

        $this->mock
            ->allows('error')
            ->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getEdgeClusterUuid($this->router()));
        $this->assertEquals(
            $this->router()->id . ' : Availability Zone `' .  $this->router()->availability_zone_id .
            '` not found',
            $message
        );
    }

    public function testGetEdgeClusterUuidGuzzleException()
    {
        $tag = 'az-default';
        $this->mock->allows('getT0Tag')->andReturn($tag);

        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . $tag
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getEdgeClusterUuid($this->router()));
        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testGetEdgeClusterUuidZeroResultCount()
    {
        $tag = 'az-default';
        $this->mock->allows('getT0Tag')->andReturn($tag);

        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . $tag
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0
                ]));
            });

        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getEdgeClusterUuid($this->router()));
        $this->assertEquals($this->router()->id . ' : No results found.', $message);
    }

    public function testGetEdgeClusterUuidPositiveResult()
    {
        $uuid = Str::uuid();
        $tag = 'az-default';
        $this->mock->allows('getT0Tag')->andReturn($tag);

        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                'api/v1/search/query?query=resource_type:EdgeCluster' .
                '%20AND%20tags.scope:' . config('defaults.tag.scope') .
                '%20AND%20tags.tag:' . $tag
            )->andReturnUsing(function () use ($uuid) {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => [
                        [
                            'id' => $uuid,
                        ]
                    ]
                ]));
            });

        $this->assertEquals($uuid, $this->mock->getEdgeClusterUuid($this->router()));
    }

    public function testGetExistingEdgeClusterUuidGuzzleException()
    {
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(
                    FixEdgeClusters::EDGE_CLUSTER_URI,
                    $this->router()->id,
                    $this->router()->id
                )
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getExistingEdgeClusterUuid($this->router()));
        $this->assertEquals($this->router()->id . ' : Not Found', $message);
    }

    public function testGetExistingEdgeClusterUuid()
    {
        $uuid = Str::uuid();
        $this->nsxServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(
                    FixEdgeClusters::EDGE_CLUSTER_URI,
                    $this->router()->id,
                    $this->router()->id
                )
            )->andReturnUsing(function () use ($uuid) {
                return new Response(200, [], json_encode([
                    'edge_cluster_path' => FixEdgeClusters::EDGE_CLUSTER_PREFIX . $uuid
                ]));
            });

        $this->assertEquals($uuid, $this->mock->getExistingEdgeClusterUuid($this->router()));
    }

    public function testUpdateEdgeClusterIdTestRun()
    {
        $uuid = Str::uuid();
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnTrue();

        $this->assertTrue($this->mock->updateEdgeClusterId($this->router(), $uuid));

        $this->assertEquals(
            'Updating router ' . $this->router()->id . '(' . $this->router()->name . ')',
            $message
        );
    }

    public function testUpdateEdgeClusterIdGuzzleException()
    {
        $uuid = Str::uuid();
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($errorMessage));

        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs(
                sprintf(FixEdgeClusters::EDGE_CLUSTER_URI, $this->router()->id, $this->router()->id)
            )->andThrow(
                new ClientException(
                    'Not Found',
                    new Request('GET', '/'),
                    new Response(404)
                )
            );

        $this->assertFalse($this->mock->updateEdgeClusterId($this->router(), $uuid));

        $this->assertEquals(
            'Updating router ' . $this->router()->id . '(' . $this->router()->name . ')',
            $message
        );
        $this->assertEquals($this->router()->id . ' : Not Found', $errorMessage);
    }

    public function testUpdateEdgeClusterId()
    {
        $uuid = Str::uuid();
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnFalse();

        $this->nsxServiceMock()
            ->allows('patch')
            ->withSomeOfArgs(
                sprintf(FixEdgeClusters::EDGE_CLUSTER_URI, $this->router()->id, $this->router()->id)
            )->andReturnUsing(function () {
                return new Response(200);
            });
        $this->assertTrue($this->mock->updateEdgeClusterId($this->router(), $uuid));
    }

    public function testHandleGetEdgeClusterUuidFails()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('getEdgeClusterUuid')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : skipped, Edge Cluster ID could not be determined.',
            $message
        );
    }

    public function testHandleGetExistingEdgeClusterUuidFails()
    {
        $uuid = Str::uuid();
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('getEdgeClusterUuid')->withAnyArgs()->andReturn($uuid);
        $this->mock->allows('getExistingEdgeClusterUuid')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : skipped, Existing Edge Cluster ID could not be determined.',
            $message
        );
    }

    public function testHandleCorrectAndExistingUuidsAreSame()
    {
        $uuid = Str::uuid();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('getEdgeClusterUuid')->withAnyArgs()->andReturn($uuid);
        $this->mock->allows('getExistingEdgeClusterUuid')->withAnyArgs()->andReturn($uuid);
        $this->mock->allows('info')->with(\Mockery::capture($message));

        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : skipped, edge_cluster_path is correct.',
            $message
        );
    }

    public function testHandleUpdateEdgeClusterIdFails()
    {
        $uuid = Str::uuid();
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('getEdgeClusterUuid')->withAnyArgs()->andReturn($uuid);
        $this->mock->allows('getExistingEdgeClusterUuid')->withAnyArgs()->andReturn(Str::uuid());
        $this->mock->allows('updateEdgeClusterId')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : edge_cluster_path failed modification.',
            $message
        );
    }

    public function testHandleUpdateEdgeClusterIdSucceeds()
    {
        $uuid = Str::uuid();
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('getEdgeClusterUuid')->withAnyArgs()->andReturn($uuid);
        $this->mock->allows('getExistingEdgeClusterUuid')->withAnyArgs()->andReturn(Str::uuid());
        $this->mock->allows('updateEdgeClusterId')->withAnyArgs()->andReturnTrue();
        $this->mock->allows('info')->with(\Mockery::capture($message));

        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : edge_cluster_path successfully modified.',
            $message
        );
    }
}
