<?php

namespace Tests\unit;

use App\Models\V2\AvailabilityZone;
use App\Models\V2\Instance;
use App\Models\V2\Region;
use App\Models\V2\TaskJobStatus;
use App\Models\V2\Vpc;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class TrackableTraitTest extends TestCase
{
    use DatabaseMigrations;

    protected $availability_zone;
    protected $instance;
    protected $region;
    protected $vpc;

    public function setUp(): void
    {
        parent::setUp();
        $this->region = factory(Region::class)->create();
        $this->availability_zone = factory(AvailabilityZone::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->vpc = factory(Vpc::class)->create([
            'region_id' => $this->region->getKey()
        ]);
        $this->instance = factory(Instance::class)->create([
            'vpc_id' => $this->vpc->getKey(),
            'name' => 'default',
        ]);
    }

    /**
     * Test instance task_running attribute is true with running task
     */
    public function testTaskRunningWithRunningTask()
    {
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_EXECUTING,
        ]);

        $this->assertTrue($this->instance->task_running);
    }

    /**
     * Test instance task_running attribute is false with finished tasks
     */
    public function testTaskRunningWithFinishedTasks()
    {
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FINISHED,
        ]);
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FAILED,
        ]);

        $this->assertFalse($this->instance->task_running);
    }

    /**
     * Test resource status attribute is ready when there are no tasks running
     */
    public function testStatusReadyWithNoTasksRunning()
    {
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FINISHED,
        ]);
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FINISHED,
        ]);

        $this->assertEquals(Instance::STATUS_READY, $this->instance->status);
    }

    /**
     * Test resource status attribute is failed when last task failed
     */
    public function testStatusFailedWithLastTaskFailed()
    {
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FINISHED,
        ]);

        sleep(1);

        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FAILED,
        ]);

        $this->assertEquals(Instance::STATUS_FAILED, $this->instance->status);
    }

    /**
     * Test resource status attribute is provisioning with running task
     */
    public function testStatusProvisioningWithRunningTask()
    {
        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_QUEUED,
        ]);

        $this->assertEquals(Instance::STATUS_PROVISIONING, $this->instance->status);
    }

    /**
     * Test resource status attribute is provisioning with running task
     */
    public function testStatusProvisioningWithRunningTaskAndFinishedTask()
    {
        $task = $this->instance->tasks()->create();
        $task->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_QUEUED,
        ]);

        $task->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_FINISHED,
        ]);

        $this->assertEquals(Instance::STATUS_PROVISIONING, $this->instance->status);
    }

    /**
     * Test resource status attribute is provisioning with task containing no jobs
     */
    public function testStatusProvisioningWithJoblessTask()
    {
        $this->instance->tasks()->create();

        $this->assertEquals(Instance::STATUS_PROVISIONING, $this->instance->status);
    }

    /**
     * Test createTask() throws with running task for resource
     */
    public function testCreateTaskThrowsWithRunningTask()
    {
        $this->expectException(\Exception::class);

        $this->instance->tasks()->create()->jobStatuses()->create([
            'type' => 'testjob',
            'status' => TaskJobStatus::STATUS_QUEUED,
        ]);

        $this->instance->createTask();
    }
}
