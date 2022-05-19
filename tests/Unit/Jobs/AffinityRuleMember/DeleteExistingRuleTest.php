<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\DeleteExistingRule;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteExistingRuleTest extends TestCase
{
    public Task $task;
    public $job;
    public AffinityRule $affinityRule;
    public AffinityRuleMember $affinityRuleMember;

    public function setUp(): void
    {
        parent::setUp();
        $this->hostGroup();
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
            $task->resource()->associate($this->affinityRuleMember);
            $task->save();
            return $task;
        });
        $this->job = \Mockery::mock(DeleteExistingRule::class, [$this->task])
            ->makePartial();
    }

    public function testDeleteRuleIfExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_CONSTRAINT_URI, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->affinityRule->id,
                    ]
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::DELETE_CONSTRAINT_URI, $this->hostGroup()->id, $this->affinityRule->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testIfHostGroupIsInvalid()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => 'hg-xxxxxxxx',
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testAffinityRuleDoesNotExist()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $uri = sprintf(DeleteExistingRule::GET_CONSTRAINT_URI, $this->hostGroup()->id);
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs($uri)
            ->andThrow(
                new ClientException('Not Found', new Request('GET', $uri), new Response(404))
            );

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeleteConstraintFails()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(DeleteExistingRule::GET_CONSTRAINT_URI, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->affinityRule->id,
                    ]
                ]));
            });

        $uri = sprintf(DeleteExistingRule::DELETE_CONSTRAINT_URI, $this->hostGroup()->id, $this->affinityRule->id);
        $this->kingpinServiceMock()
            ->expects('delete')
            ->withSomeOfArgs($uri)
            ->andThrow(
                new ClientException('Not Found', new Request('GET', $uri), new Response(404))
            );

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
