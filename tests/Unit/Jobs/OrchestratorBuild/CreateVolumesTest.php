<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\CreateVolumes;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateVolumesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;

    protected OrchestratorBuild $orchestratorBuild;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create([
            'data' => json_encode([
                'volumes' => [
                    [
                        'instance_id' => '{instance.0}',
                        'vpc_id' => '{vpc.0}',
                        'name' => 'test volume',
                        'availability_zone_id' => $this->availabilityZone()->id,
                        'capacity' => 10,
                        'iops' => 300
                    ]
                ]
            ])
        ]);

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testNoVolumeDataSkips()
    {
        $this->orchestratorConfig->data = null;
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new CreateVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testResourceAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('volume', 0, 'vol-test');
        $this->orchestratorBuild->updateState('instance', 0, $this->instanceModel()->id);

        dispatch(new CreateVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['volume']));
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');
        $this->orchestratorBuild->updateState('instance', 0, $this->instanceModel()->id);

        dispatch(new CreateVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['volume']));
    }

    public function testIdPlaceholdersIgnoredSuccess()
    {
        $this->orchestratorConfig->data = json_encode([
            'volumes' => [
                [
                    'id' => '{volume.0}',
                    'name' => 'test volume',
                    'vpc_id' => '{vpc.0}',
                    'availability_zone_id' => $this->availabilityZone()->id,
                    'capacity' => 10,
                    'iops' => 300
                ]
            ]
        ]);
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->availabilityZone();
        $this->vpc();
        $this->orchestratorBuild->updateState('vpc', 0, 'vpc-test');
        $this->orchestratorBuild->updateState('instance', 0, $this->instanceModel()->id);

        dispatch(new CreateVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['volume']));
    }
}
