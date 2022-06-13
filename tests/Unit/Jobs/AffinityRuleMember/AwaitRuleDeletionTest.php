<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\AwaitRuleDeletion;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AwaitRuleDeletionTest extends TestCase
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
        $this->job = \Mockery::mock(AwaitRuleDeletion::class, [$this->task])
            ->makePartial();
    }

    public function testWaitIfRulePresent()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Waiting');

        $instanceMock = \Mockery::mock($this->instanceModel())->makePartial();
        $instanceMock->allows('hasAffinityRule')->withAnyArgs()->andReturnTrue();

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $instanceMock->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_CONSTRAINT_URI, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => $this->affinityRule->id,
                        'constraintType' => 'InstanceAffinity',
                        'enabled' => true,
                    ]
                ]));
            });

        $this->job->expects('release')
            ->withAnyArgs()
            ->andThrows(new \Exception('Waiting'));

        $this->job->handle();
    }

    public function testSkipIfRuleNotPresent()
    {

        $instanceMock = \Mockery::mock($this->instanceModel())->makePartial();
        $instanceMock->allows('hasAffinityRule')->withAnyArgs()->andReturnTrue();

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $instanceMock->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_CONSTRAINT_URI, $this->hostGroup()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    [
                        'ruleName' => 'ar-xxxxxxxx',
                        'constraintType' => 'InstanceAffinity',
                        'enabled' => true,
                    ]
                ]));
            });

        $this->job->allows('info')
            ->with(
                \Mockery::capture($message),
                \Mockery::capture($affinityRule)
            );

        $this->job->handle();
        $this->assertEquals('Rule deletion complete', $message);
        $this->assertEquals($this->affinityRule->id, $affinityRule['affinity_rule_id']);
    }
}
