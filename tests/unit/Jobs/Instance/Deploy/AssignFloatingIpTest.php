<?php
namespace Tests\unit\Jobs\Instance\Deploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\AssignFloatingIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AssignFloatingIpTest extends TestCase
{
    public function testNoFloatingIpIdInDeploymentDataSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AssignFloatingIp($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpAssignJobIsDispatched()
    {
        Event::fake([JobProcessed::class, Created::class]);

        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());

        $deploy_data = $this->instanceModel()->deploy_data;
        $deploy_data['floating_ip_id'] = $this->floatingIp()->id;
        $this->instanceModel()->deploy_data = $deploy_data;
        $this->instanceModel()->save();

        $job = \Mockery::mock(AssignFloatingIp::class, [$this->instanceModel()])->makePartial();
        $job->shouldReceive('awaitTask')->andReturn(true);
        $job->handle();

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_assign';
        });
    }

    public function testAwaitAssignFloatingIpTaskTaskFailed()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());

        $deploy_data = $this->instanceModel()->deploy_data;
        $deploy_data['floating_ip_id'] = $this->floatingIp()->id;
        $this->instanceModel()->deploy_data = $deploy_data;
        $this->instanceModel()->save();

        $task = new Task([
            'id' => 'task-test',
            'completed' => false,
            'failure_reason' => 'Test Failure',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        // Bind and return test ID on creation
        app()->bind(Task::class, function () use ($task) {
            return $task;
        });

        dispatch(new AssignFloatingIp($this->instanceModel()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testAwaitAssignFloatingIpTaskTaskSucceeded()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $ipAddress = IpAddress::factory()->create();
        $ipAddress->nics()->sync($this->nic());

        $deploy_data = $this->instanceModel()->deploy_data;
        $deploy_data['floating_ip_id'] = $this->floatingIp()->id;
        $this->instanceModel()->deploy_data = $deploy_data;
        $this->instanceModel()->save();

        $task = new Task([
            'id' => 'task-test',
            'completed' => true,
            'name' => 'floating_ip_assign'
        ]);
        $this->floatingIp()->tasks()->save($task);

        // Bind and return test ID on creation
        app()->bind(Task::class, function () use ($task) {
            return $task;
        });

        $job = new AssignFloatingIp($this->instanceModel());

        $job->awaitTask($task);

        $this->assertTrue($job->awaitTask($task));
    }
}