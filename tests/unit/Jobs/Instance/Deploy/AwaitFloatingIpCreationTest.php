<?php
namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\AwaitFloatingIpCreation;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitFloatingIpCreationTest extends TestCase
{
    public function testRequiresFloatingIpFalseSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpCreation($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testNoFloatingIpIdInDeploymentDataWhenRequiresFloatingIpTrueFails()
    {
        $instance = Instance::withoutEvents(function() {
            return Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'deploy_data' => [
                    'requires_floating_ip' => true,
                ]
            ]);
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpCreation($instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testFloatingIpSyncFail()
    {
        $task = new Task([
            'completed' => true,
            'failure_reason' => 'Test Failure',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        $instance = Instance::withoutEvents(function() {
            return Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'deploy_data' => [
                    'requires_floating_ip' => true,
                    'floating_ip_id' => 'fip-test',
                ]
            ]);
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpCreation($instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testFloatingIpSyncInProgress()
    {
        $task = new Task([
            'completed' => false,
            'failure_reason' => null,
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        $instance = Instance::withoutEvents(function() {
            return Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'deploy_data' => [
                    'requires_floating_ip' => true,
                    'floating_ip_id' => 'fip-test',
                ]
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitFloatingIpCreation($instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    public function testSuccessful()
    {
        $task = new Task([
            'completed' => false,
            'failure_reason' => null,
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->floatingIp()->tasks()->save($task);

        $instance = Instance::withoutEvents(function() {
            return Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'deploy_data' => [
                    'requires_floating_ip' => true,
                    'floating_ip_id' => 'fip-test',
                ]
            ]);
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitFloatingIpCreation($instance));

        Event::assertNotDispatched(JobFailed::class);
    }
}