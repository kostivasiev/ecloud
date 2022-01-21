<?php

namespace Tests\unit\Jobs\Vip;

use App\Events\V2\Task\Created;
use App\Jobs\Vip\CreateFloatingIp;
use App\Models\V2\FloatingIp;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VipMock;
use Tests\TestCase;

class CreateFloatingIpTest extends TestCase
{
    use VipMock;

    public function testCreatesFloatingIp()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $this->vip()->assignClusterIp();

        $task = $this->createSyncUpdateTask($this->vip(), ['allocate_floating_ip' => true]);

        dispatch(new CreateFloatingIp($task));

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->resource instanceof FloatingIp
                && $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $task->refresh();

        $this->assertNotNull($task->data['floating_ip_id']);

        // Mark the sync as completed
        $syncTask = Event::dispatched(\App\Events\V2\Task\Created::class, function ($event) {
            return $event->model->name == Sync::TASK_NAME_UPDATE;
        })->first()[0];

        $syncTask->model->setAttribute('completed', true)->saveQuietly();

        dispatch(new CreateFloatingIp($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpAlreadyCreatedSkips()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->vip(), ['allocate_floating_ip' => true]);

        $task->setAttribute('data', ['floating_ip_id' => 'test'])->saveQuietly();

        dispatch(new CreateFloatingIp($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(Created::class);
    }

    public function testFloatingIpNotRequiredSkips()
    {
        Event::fake([JobProcessed::class, JobFailed::class, Created::class]);

        $task = $this->createSyncUpdateTask($this->vip(), ['allocate_floating_ip' => false]);

        dispatch(new CreateFloatingIp($task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(Created::class);
    }
}