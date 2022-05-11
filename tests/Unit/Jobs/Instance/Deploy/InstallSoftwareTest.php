<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\InstallSoftware;
use App\Models\V2\Task;
use App\Support\Sync;
use Database\Seeders\Images\CentosWithMcafeeSeeder;
use Database\Seeders\SoftwareSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class InstallSoftwareTest extends TestCase
{
    private Task $task;

    public function setUp(): void
    {
        parent::setUp();
        (new SoftwareSeeder())->run();

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instanceModel())->save();
        });
    }

    public function testNoSoftwarePasses()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new InstallSoftware($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testImageAssociatedSoftwareInstalls()
    {
        (new CentosWithMcafeeSeeder())->run();

        Event::fake([Created::class, JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('image_id', 'img-centos-mcafee')->save();

        dispatch(new InstallSoftware($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->task->refresh();

        $this->assertTrue(isset($this->task->data['instance_software_ids']));
        $this->assertEquals(1, count($this->task->data['instance_software_ids']));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testOptionalSoftwareInstalls()
    {
        Event::fake([Created::class, JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('deploy_data', [
            'software_ids' => [
                'soft-aaaaaaaa'
            ]
        ])->save();

        dispatch(new InstallSoftware($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->task->refresh();

        $this->assertTrue(isset($this->task->data['instance_software_ids']));
        $this->assertEquals(1, count($this->task->data['instance_software_ids']));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testImageAssociatedSoftwareAndOptionalSoftwareInstalls()
    {
        Event::fake([Created::class, JobFailed::class, JobProcessed::class]);

        (new CentosWithMcafeeSeeder())->run();

        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->setAttribute('image_id', 'img-centos-mcafee')->save();

        $this->instanceModel()->setAttribute('deploy_data', [
            'software_ids' => [
                'soft-aaaaaaaa'
            ]
        ])->save();

        dispatch(new InstallSoftware($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->task->refresh();

        $this->assertTrue(isset($this->task->data['instance_software_ids']));
        $this->assertEquals(2, count($this->task->data['instance_software_ids']));
    }
}
