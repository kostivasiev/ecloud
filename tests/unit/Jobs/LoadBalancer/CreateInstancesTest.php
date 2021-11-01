<?php

namespace Tests\unit\Jobs\LoadBalancer;

use App\Events\V2\Task\Created;
use App\Jobs\LoadBalancer\CreateInstances;
use App\Models\V2\ImageMetadata;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateInstancesTest extends TestCase
{
    use LoadBalancerMock;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccess()
    {
        // Create the load balancer image and image metadata
        factory(ImageMetadata::class)->create([
            'image_id' => $this->image()->id,
            'key' => 'ukfast.loadbalancer.version',
            'value' => 'Ubuntu-20.04-LBv2',
        ]);

        // Create the management network
        $this->router()->setAttribute('is_management', true)->save();
        $this->network();

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

        dispatch(new CreateInstances($task));

        Event::assertDispatched(Created::class, function ($event) {
            return (
                $event->model->resource instanceof OrchestratorBuild
                && $event->model->name == Sync::TASK_NAME_UPDATE
            );
        });

        Event::assertNotDispatched(JobFailed::class);

        $task->refresh();

        $this->assertNotNull($task->data['orchestrator_build_id']);
    }


}
