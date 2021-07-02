<?php
namespace Tests\unit\Jobs\OrchestratorBuild;

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
    protected $job;

    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = factory(OrchestratorConfig::class)->create([
            'data' => json_encode([
                'router' => [
                    [
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test router',
                        'router_throughput_id' => "rtp-test",
                        'availability_zone_id' => "az-test"
                    ]
                ]
            ])
        ]);

        $this->routerThroughput();

        $this->orchestratorBuild = factory(OrchestratorBuild::class)->make();
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


    public function testDefaultAvailabilityZoneSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorConfig->data = json_encode([
                'router' => [
                    [
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test router',
                        'router_throughput_id' => "rtp-test"
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

    public function testDefaultRouterThroughputId()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorConfig->data = json_encode([
            'router' => [
                [
                    'vpc_id' => '{vpc.0}',
                    'name' => 'test router',
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
}