<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\AwaitRuleCreation;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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

        $this->task->updateData('created_rules', [$this->hostGroup()->id]);

        $this->job->expects('affinityRuleExists')
            ->withAnyArgs()
            ->andReturnFalse();
        $this->job->expects('release')
            ->withAnyArgs()
            ->andThrows(new \Exception('Waiting'));

        $this->job->handle();
    }

    public function testSkipIfRulePresent()
    {
        Log::shouldReceive('info')
            ->with(
                \Mockery::capture($message),
                \Mockery::capture($affinityRule)
            );
        $this->task->updateData('created_rules', [$this->hostGroup()->id]);

        $this->job->expects('affinityRuleExists')
            ->withAnyArgs()
            ->andReturnTrue();

        $this->job->handle();
        $this->assertEquals('Rule creation complete', $message);
        $this->assertEquals($this->affinityRule->id, $affinityRule['affinity_rule_id']);
    }
}
