<?php

namespace Tests\Unit\Jobs\AffinityRule;

use App\Jobs\AffinityRule\DeleteExistingRule;
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
            $task->resource()->associate($this->affinityRule);
            $task->save();
            return $task;
        });
        $this->job = \Mockery::mock(DeleteExistingRule::class, [$this->task])
            ->makePartial();
    }

    public function testDeleteRuleIfExists()
    {
        $this->job
            ->expects('affinityRuleExists')
            ->withAnyArgs()
            ->andReturnTrue();

        $uri = sprintf(
            DeleteExistingRule::DELETE_CONSTRAINT_URI,
            $this->hostGroup()->id,
            $this->affinityRule->id
        );

        $this->kingpinServiceMock()
            ->expects('delete')
            ->with($uri)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->job->handle();

        $this->assertEquals($this->hostGroup()->id, $this->task->data['existing_rules'][0]);
    }

    public function testNoActionIfNotExists()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(sprintf(DeleteExistingRule::GET_CONSTRAINT_URI, $this->hostGroup()->id))
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([[]]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeleteThrowsError()
    {
        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(sprintf(DeleteExistingRule::GET_CONSTRAINT_URI, $this->hostGroup()->id))
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->affinityRule->id,
                        'constraintType' => 'HostAffinity',
                        'enabled' => true,
                    ]
                ]));
            });

        $uri = sprintf(DeleteExistingRule::DELETE_CONSTRAINT_URI, $this->hostGroup()->id, $this->affinityRule->id);

        $this->kingpinServiceMock()
            ->expects('delete')
            ->with($uri)
            ->andThrows(
                new ClientException(
                    'Not Found',
                    new Request('delete', $uri),
                    new Response(404)
                )
            );

        Event::fake([JobFailed::class]);

        dispatch(new DeleteExistingRule($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
