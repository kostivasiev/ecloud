<?php

namespace Tests\Unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\Create;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateTest extends TestCase
{
    protected $dhcp;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->dhcp);
            $this->task->save();
        });
    }

    public function testSucceedsStandardNetworking()
    {
        $this->nsxServiceMock()->expects('get')
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

        $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/policy/api/v1/infra/dhcp-server-configs/dhcp-test',
                [
                    'json' => [
                        'lease_time' => config('defaults.dhcp.lease_time'),
                        'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/STANDARD-EDGE-CLUSTER-ID',
                        'resource_type' => 'DhcpServerConfig',
                        'tags' => [
                            [
                                'scope' => config('defaults.tag.scope'),
                                'tag' => $this->vpc()->id
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Create($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSucceedsAdvancedNetworking()
    {
        $this->vpc()->setAttribute('advanced_networking', true)->save();

        $this->nsxServiceMock()->expects('get')
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

        $this->nsxServiceMock()->expects('put')
            ->withArgs([
                '/policy/api/v1/infra/dhcp-server-configs/dhcp-test',
                [
                    'json' => [
                        'lease_time' => config('defaults.dhcp.lease_time'),
                        'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/ADVANCED-NETWORKING-EDGE-CLUSTER-ID',
                        'resource_type' => 'DhcpServerConfig',
                        'tags' => [
                            [
                                'scope' => config('defaults.tag.scope'),
                                'tag' => $this->vpc()->id
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Create($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFails()
    {
        $this->nsxServiceMock()->expects('get')
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
                            'id' => 'ADVANCED-NETWORKING-EDGE-CLUSTER-ID'
                        ]
                    ]
                ]));
            });

        $this->expectException(\Exception::class);

        $this->nsxServiceMock()->expects('put')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/dhcp-test')
            ->andThrows(new \Exception());

        dispatch(new Create($this->task));
    }
}
