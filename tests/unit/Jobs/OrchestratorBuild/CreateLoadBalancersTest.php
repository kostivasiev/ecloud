<?php
namespace Tests\unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateLoadBalancers;
use App\Jobs\OrchestratorBuild\CreateRouters;
use App\Models\V2\LoadBalancer;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateLoadBalancersTest extends TestCase
{
    use LoadBalancerMock;

    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create([
            'data' => json_encode([
                'load-balancers' => [
                    [
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test load balancer',
                        'availability_zone_id' => $this->availabilityZone()->id,
                        'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                    ]
                ]
            ])
        ]);

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testNoLoadBalancerDataSkips()
    {
        $this->orchestratorConfig->setAttribute('data', null)->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        dispatch(new CreateLoadBalancers($this->orchestratorBuild));

        Event::assertNotDispatched(Created::class);
        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testResourceAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('load-balancer', 0, 'lb-test');

        dispatch(new CreateLoadBalancers($this->orchestratorBuild));

        Event::assertNotDispatched(Created::class);
        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['load-balancer']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['load-balancer']));
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        dispatch(new CreateLoadBalancers($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class, function ($event) {
            return
                $event->model->resource instanceof LoadBalancer &&
                $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['load-balancer']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['load-balancer']));
    }

    public function testIdPlaceholdersIgnoredSuccess()
    {
        $this->orchestratorConfig->setAttribute('data', json_encode([
            'load-balancers' => [
                [
                    'id' => '{load-balancer.0}',
                    'vpc_id' => '{vpc.0}',
                    'name' => 'test load balancer',
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'load_balancer_spec_id' => $this->loadBalancerSpecification()->id,
                ]
            ]
        ]))->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        dispatch(new CreateLoadBalancers($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class, function ($event) {
            return
                $event->model->resource instanceof LoadBalancer &&
                $event->model->name == Sync::TASK_NAME_UPDATE;
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['load-balancer']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['load-balancer']));
    }
}