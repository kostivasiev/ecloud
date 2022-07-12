<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\DeleteExistingRule;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
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
        $this->task = $this->createSyncUpdateTask($this->affinityRuleMember);
        $this->task->setAttribute('completed', false)->saveQuietly();
        $this->job = \Mockery::mock(DeleteExistingRule::class, [$this->task])
            ->makePartial();
    }

    public function testDeleteRuleIfExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_CONSTRAINT_URI, $this->hostGroup()->id)
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
                sprintf(KingpinService::DELETE_CONSTRAINT_URI, $this->hostGroup()->id, $this->affinityRule->id)
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
        $hostGroupId = 'hg-xxxxxxxx';
        $this->instanceModel()->setAttribute('host_group_id', null)->save();

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () use ($hostGroupId) {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $hostGroupId,
                ]));
            });

        $uri = sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroupId);
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs($uri)
            ->andThrow(
                new ClientException('Not Found', new Request('GET', $uri), new Response(404))
            );

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertDispatched(JobProcessed::class);
    }

    public function testAffinityRuleDoesNotExist()
    {
        $instanceMock = \Mockery::mock($this->instanceModel())->makePartial();
        $instanceMock->allows('hasAffinityRule')->withAnyArgs()->andReturnFalse();
        $this->instanceModel()->setAttribute('host_group_id', null)->save();

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $instanceMock->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $uri = sprintf(KingpinService::GET_CONSTRAINT_URI, $this->hostGroup()->id);
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
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_CONSTRAINT_URI, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->affinityRule->id,
                    ]
                ]));
            });

        $uri = sprintf(KingpinService::DELETE_CONSTRAINT_URI, $this->hostGroup()->id, $this->affinityRule->id);
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
