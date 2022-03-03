<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployRouterLocaleTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->router());
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

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id,
                [
                    'json' => [
                        'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/STANDARD-EDGE-CLUSTER-ID',
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
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRouterLocale($this->task));

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

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id,
                [
                    'json' => [
                        'edge_cluster_path' => '/infra/sites/default/enforcement-points/default/edge-clusters/ADVANCED-NETWORKING-EDGE-CLUSTER-ID',
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
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRouterLocale($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
