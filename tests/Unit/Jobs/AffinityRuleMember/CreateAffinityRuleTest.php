<?php

namespace Tests\Unit\Jobs\AffinityRuleMember;

use App\Jobs\AffinityRuleMember\CreateAffinityRule;
use App\Models\V2\AffinityRule;
use App\Models\V2\AffinityRuleMember;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
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
    public Instance $secondInstance;

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
            $task->resource()->associate($this->affinityRuleMember);
            $task->save();
            return $task;
        });
        $this->job = \Mockery::mock(CreateAffinityRule::class, [$this->task])
            ->makePartial();
        $this->hostGroup();
    }

    public function testSuccessfulCreation()
    {
        $this->createSecondaryMember()
            ->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs(sprintf(KingpinService::ANTI_AFFINITY_URI, $this->hostGroup()->id))
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->secondInstance->id)
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $this->kingpinServiceMock()
            ->expects('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
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
        $this->createSecondaryMember();

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
                sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc()->id, $this->secondInstance->id)
            )
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'hostGroupID' => $this->hostGroup()->id,
                ]));
            });

        $uri = sprintf(KingpinService::ANTI_AFFINITY_URI, $this->hostGroup()->id);
        $this->kingpinServiceMock()
            ->expects('post')
            ->withSomeOfArgs($uri)
            ->andThrow(new RequestException('Error', new Request('POST', $uri)));

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new CreateAffinityRule($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNoActionWhenNoMembers()
    {
        $this->affinityRuleMember->setAttribute('deleted_at', Carbon::now())->save();
        $this->setExceptionExpectations('info', 'Rule has no members, skipping');

        $this->job->handle();
    }

    public function testNoActionWhenFewerThanTwoMembers()
    {
        Log::shouldReceive('info')
            ->with(
                \Mockery::capture($message),
                \Mockery::capture($data)
            );

        $this->job->handle();
        $this->assertEquals('Affinity rules need at least two members', $message);
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

    private function createSecondaryMember(): self
    {
        $this->secondInstance = Instance::withoutEvents(function () {
            return Instance::factory()->create([
                'id' => 'i-test-2',
                'vpc_id' => $this->vpc()->id,
                'name' => 'Test Instance ' . uniqid(),
                'image_id' => $this->image()->id,
                'vcpu_cores' => 1,
                'ram_capacity' => 1024,
                'availability_zone_id' => $this->availabilityZone()->id,
                'deploy_data' => [
                    'network_id' => $this->network()->id,
                    'volume_capacity' => 20,
                    'volume_iops' => 300,
                    'requires_floating_ip' => false,
                ]
            ]);
        });
        AffinityRuleMember::factory()
            ->for($this->affinityRule)
            ->create([
                'instance_id' => $this->secondInstance,
            ]);
        return $this;
    }
}
