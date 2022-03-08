<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AwaitLoadBalancers;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class AwaitLoadBalancersTest extends TestCase
{
    use LoadBalancerMock;

    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityZone();

        $this->orchestratorConfig = OrchestratorConfig::factory()->create();
        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testResourceInProgressReleasedBackIntoQueue()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->orchestratorBuild->updateState('load_balancer', 0, $this->loadBalancer()->id);

        $this->createSyncUpdateTask($this->loadBalancer());

        dispatch(new AwaitLoadBalancers($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }

    function testFailedResourceFailsJob()
    {
        Event::fake(JobFailed::class);

        $this->orchestratorBuild->updateState('load_balancer', 0, $this->loadBalancer()->id);

        $this->createSyncUpdateTask($this->loadBalancer())
            ->setAttribute('completed', false)
            ->setAttribute('failure_reason', 'some failure reason')
            ->saveQuietly();

        dispatch(new AwaitLoadBalancers($this->orchestratorBuild));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('load_balancer', 0, $this->loadBalancer()->id);

        $this->createSyncUpdateTask($this->loadBalancer())
            ->setAttribute('completed', true)
            ->saveQuietly();

        $job = new AwaitLoadBalancers($this->orchestratorBuild);

        dispatch($job);

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testTimeout()
    {
        Event::fake([Created::class]);

        $this->orchestratorBuild->updateState('load_balancer', 0, $this->loadBalancer()->id);

        $this->createSyncUpdateTask($this->loadBalancer())
            ->setAttribute('completed', true)
            ->saveQuietly();

        $job = new AwaitLoadBalancers($this->orchestratorBuild);

        $job->handle();

        $this->assertEquals(480, $job->tries);
    }
}