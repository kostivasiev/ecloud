<?php

namespace Tests\Unit\Jobs\AffinityRule;

use App\Jobs\AffinityRule\CheckMemberState;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CheckMemberStateTest extends TestCase
{
    public Task $task;
    public $job;
    public AffinityRule $affinityRule;
    public AffinityRuleMember $affinityRuleMember;

    public function setUp(): void
    {
        parent::setUp();
        $this->affinityRule = AffinityRule::factory()
            ->create([
            'vpc_id' => $this->vpc(),
            'availability_zone_id' => $this->availabilityZone(),
            'type' => 'anti-affinity',
        ]);
        $this->affinityRuleMember = AffinityRuleMember::factory()
            ->for($this->affinityRule)
            ->create([
                'instance_id' => $this->instanceModel(),
            ]);
        $this->task = Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'ar-task-1',
                'completed' => false,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->affinityRule);
            $task->save();
            return $task;
        });
        $this->job = \Mockery::mock(CheckMemberState::class, [$this->task])
            ->makePartial();
    }

    public function testCompletedState()
    {
        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $task->resource()->associate($this->affinityRuleMember);
            $task->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new CheckMemberState($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testInProgressState()
    {
        $this->setExceptionExpectations('release', 'Job Released');

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'completed' => false,
            ]);
            $task->resource()->associate($this->affinityRuleMember);
            $task->save();
        });

        $this->job->handle();
    }

    public function testInFailedState()
    {
        $this->setExceptionExpectations('fail', 'Job Failed');

        Model::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
                'completed' => false,
                'failure_reason' => 'The task has failed',
            ]);
            $task->resource()->associate($this->affinityRuleMember);
            $task->save();
        });

        $this->job->handle();
    }

    private function setExceptionExpectations(string $method, string $message): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);

        $this->job
            ->expects($method)
            ->withAnyArgs()
            ->andThrows(new \Exception($message));
    }
}
