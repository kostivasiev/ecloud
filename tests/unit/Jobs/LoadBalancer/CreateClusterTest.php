<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\CreateCluster;
use App\Models\V2\Credential;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;
use UKFast\Admin\Loadbalancers\AdminClient;
use UKFast\Admin\Loadbalancers\AdminClusterClient;
use UKFast\SDK\SelfResponse;

class CreateClusterTest extends TestCase
{
    use LoadBalancerMock;

    private $lbConfigId = 123456;

    public function setUp(): void
    {
        parent::setUp();
        app()->bind(AdminClient::class, function () {
            $mock = \Mockery::mock(AdminClient::class)->makePartial();
            $mock->allows('setResellerId')->andReturnSelf();
            $mock->allows('clusters')->andReturnUsing(function () {
                $clusterMock = \Mockery::mock(AdminClusterClient::class)->makePartial();
                $clusterMock->allows('createEntity')
                    ->withAnyArgs()
                    ->andReturnUsing(function () {
                        $responseBody = json_decode(
                            json_encode([
                                'meta' => ['location' => env('app_domain')],
                                'data' => ['id' => $this->lbConfigId],
                            ])
                        );
                        return (new SelfResponse($responseBody));
                    });
                return $clusterMock;
            });
            return $mock;
        });
        $this->loadBalancer()->setAttribute('config_id', null)->saveQuietly();
    }

    public function testCreateCluster()
    {
        $task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->loadBalancer());
            $task->save();
            return $task;
        });
        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateCluster($task));
        Event::assertNotDispatched(JobFailed::class);

        $this->assertNotNull($this->loadBalancer()->refresh()->config_id);
        $this->assertEquals($this->lbConfigId, $this->loadBalancer()->config_id);
    }
}
