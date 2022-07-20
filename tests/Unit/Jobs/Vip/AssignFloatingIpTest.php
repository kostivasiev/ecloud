<?php

namespace Tests\Unit\Jobs\Vip;

use App\Events\V2\Task\Created;
use App\Jobs\Tasks\FloatingIp\Assign;
use App\Jobs\Vip\AssignFloatingIp;
use App\Models\V2\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class AssignFloatingIpTest extends TestCase
{
    use VipMock;

    public function testAssignFloatingIpSuccess()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $this->vip()->assignClusterIp();

        $task = $this->createSyncUpdateTask($this->vip(), [
            'allocate_floating_ip' => true,
            'floating_ip_id' => $this->floatingIp()->id
        ]);

        dispatch(new AssignFloatingIp($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == Assign::TASK_NAME;
        });

        $task->refresh();

        $this->assertNotNull($task->data['task.' . Assign::TASK_NAME . '.ids']);

        // Mark the fip assign task as completed
        $assignTask = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Assign::TASK_NAME;
        })->first()[0];

        $assignTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new AssignFloatingIp($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testAssignFloatingIpTaskNotCompleteIsReleased()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $this->vip()->assignClusterIp();

        $floatingIpAssignTask = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'completed' => false,
                'name' => Assign::TASK_NAME,
            ]);
            $task->resource()->associate($this->floatingIp());
            $task->save();
            return $task;
        });

        $task = $this->createSyncUpdateTask($this->vip(), [
            'allocate_floating_ip' => true,
            'floating_ip_id' => $this->floatingIp()->id,
            'task.' . Assign::TASK_NAME . '.ids' => [$floatingIpAssignTask->id]
        ]);

        dispatch(new AssignFloatingIp($task));

        Event::assertNotDispatched(Created::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testFloatingIpAlreadyAssignedSkips()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $clusterIp = $this->vip()->assignClusterIp();

        $this->assignFloatingIp($this->floatingIp(), $clusterIp);

        $task = $this->createSyncUpdateTask($this->vip(), [
            'allocate_floating_ip' => true,
            'floating_ip_id' => $this->floatingIp()->id
        ]);

        dispatch(new AssignFloatingIp($task));

        Event::assertNotDispatched(Created::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}