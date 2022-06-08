<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\AwaitRuleCreation;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class AwaitRuleCreationTest extends TestCase
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
        $this->job = \Mockery::mock(AwaitRuleCreation::class, [$this->task])
            ->makePartial();
    }

    public function testWaitIfRuleNotPresent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Waiting');

        $this->job->expects('affinityRuleExists')
            ->withAnyArgs()
            ->andReturnFalse();
        $this->job->expects('release')
            ->withAnyArgs()
            ->andThrows(new \Exception('Waiting'));

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->job->handle();
    }

    public function testSkipIfRulePresent()
    {
        $this->job
            ->allows('info')
            ->with(
                \Mockery::capture($message),
                \Mockery::capture($affinityRule)
            );
        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->job->expects('affinityRuleExists')
            ->withAnyArgs()
            ->andReturnTrue();

        $this->job->handle();
        $this->assertEquals('Rule creation complete', $message);
        $this->assertEquals($this->affinityRule->id, $affinityRule['affinity_rule_id']);
    }
}
