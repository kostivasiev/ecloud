<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateRouters;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateRoutersTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create([
            'data' => json_encode([
                'routers' => [
                    [
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test router',
                        'router_throughput_id' => "rtp-test",
                        'availability_zone_id' => $this->availabilityZone()->id
                    ]
                ]
            ])
        ]);

        $this->routerThroughput();

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testNoRouterDataSkips()
    {
        $this->orchestratorConfig->data = null;
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new CreateRouters($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testResourceAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('router', 0, 'rtr-test');

        dispatch(new CreateRouters($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['router']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['router']));
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        dispatch(new CreateRouters($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['router']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['router']));
    }

    public function testDefaultRouterThroughputId()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorConfig->data = json_encode([
            'routers' => [
                [
                    'vpc_id' => '{vpc.0}',
                    'name' => 'test router',
                    'availability_zone_id' => $this->availabilityZone()->id
                ]
            ]
        ]);
        $this->orchestratorConfig->save();

        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        dispatch(new CreateRouters($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['router']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['router']));
    }

    public function testIdPlaceholdersIgnoredSuccess()
    {
        $this->orchestratorConfig->data = json_encode([
            'routers' => [
                [
                    'id' => '{router.0}',
                    'vpc_id' => '{vpc.0}',
                    'name' => 'test router',
                    'router_throughput_id' => "rtp-test",
                    'availability_zone_id' => $this->availabilityZone()->id
                ]
            ]
        ]);
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');

        dispatch(new CreateRouters($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['router']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['router']));
    }
}