<?php

namespace Tests\Unit\Jobs\AffinityRule;

use App\Jobs\AffinityRule\CreateAffinityRule;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class CreateAffinityRuleTest extends TestCase
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
        $this->job = \Mockery::mock(CreateAffinityRule::class, [$this->task])
            ->makePartial();
        $this->hostGroup();
    }

    public function testSuccessfulCreation()
    {
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs(sprintf(CreateAffinityRule::ANTI_AFFINITY_URI, $this->hostGroup()->id))
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class, JobProcessed::class, ]);

        dispatch(new CreateAffinityRule($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testExceptionDuringCreation()
    {
        $this->setExceptionExpectations('info', 'Failed to create affinity rule');

        $uri = sprintf(CreateAffinityRule::ANTI_AFFINITY_URI, $this->hostGroup()->id);
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs($uri)
            ->andThrow(new RequestException('Error', new Request('POST', $uri)));

        $this->job->handle();
    }

    public function testNoActionWhenNoMembers()
    {
        $this->affinityRuleMember->setAttribute('deleted_at', Carbon::now())->save();
        $this->setExceptionExpectations('info', 'Rule has no members, skipping');

        $this->job->handle();
    }

    private function setExceptionExpectations(string $method, string $message): self
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($message);

        Log::shouldReceive($method)
            ->withSomeOfArgs($message)
            ->andThrows(new \Exception($message));

        return $this;
    }
}
