<?php

namespace Tests\Unit\Jobs\OrchestratorBuild;

use App\Events\V2\Task\Created;
use App\Jobs\OrchestratorBuild\AttachVolumes;
use App\Models\V2\OrchestratorBuild;
use App\Models\V2\OrchestratorConfig;
use App\Models\V2\Volume;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AttachVolumesTest extends TestCase
{
    protected OrchestratorConfig $orchestratorConfig;
    protected OrchestratorBuild $orchestratorBuild;
    protected Volume $volume;

    public function setUp(): void
    {
        parent::setUp();
        $this->orchestratorConfig = OrchestratorConfig::factory()->create([
            'data' => json_encode([
                'instance_volumes' => [
                    [
                        'volume_id' => '{volume.0}',
                        'instance_id' => '{instance.0}'
                    ]
                ]
            ])
        ]);
        $this->volume = Volume::withoutEvents(function () {
            return Volume::factory()->create([
                'id' => 'vol-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
                'vmware_uuid' => 'd7a86079-6b02-4373-b2ca-6ec24fef2f1c',
            ]);
        });

        $this->orchestratorBuild = OrchestratorBuild::factory()->make();
        $this->orchestratorBuild->orchestratorConfig()->associate($this->orchestratorConfig);
        $this->orchestratorBuild->save();
    }

    public function testNoVolumeDataSkips()
    {
        $this->orchestratorConfig->data = null;
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AttachVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testResourceAlreadyExistsSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance_volume', 0, 'i-test');

        dispatch(new AttachVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['instance_volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['instance_volume']));
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance', 0, $this->instanceModel()->id);
        $this->orchestratorBuild->updateState('volume', 0, $this->volume->id);

        dispatch(new AttachVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['instance_volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['instance_volume']));
    }

    public function testIdPlaceholdersIgnoredSuccess()
    {
        $this->orchestratorConfig->data = json_encode([
            'instance_volumes' => [
                [
                    'id' => 'i-xxxxxx',
                    'volume_id' => '{volume.0}',
                    'instance_id' => '{instance.0}',
                ]
            ]
        ]);
        $this->orchestratorConfig->save();

        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->orchestratorBuild->updateState('instance', 0, $this->instanceModel()->id);
        $this->orchestratorBuild->updateState('volume', 0, $this->volume->id);

        dispatch(new AttachVolumes($this->orchestratorBuild));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);

        $this->orchestratorBuild->refresh();

        $this->assertNotNull($this->orchestratorBuild->state['instance_volume']);

        $this->assertEquals(1, count($this->orchestratorBuild->state['instance_volume']));
    }
}
