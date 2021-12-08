<?php

namespace Tests\unit\Jobs\Instance\Deploy;

use App\Jobs\Instance\Deploy\InstallSoftware;
use App\Models\V2\FloatingIp;
use App\Models\V2\InstanceSoftware;
use App\Models\V2\Task;
use App\Support\Sync;
use Database\Seeders\Images\CentosWithMcafeeSeeder;
use Database\Seeders\Software\McafeeLinuxSoftwareSeeder;
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
        (new McafeeLinuxSoftwareSeeder())->run();
        (new CentosWithMcafeeSeeder())->run();

        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->instance())->save();
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
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instance()->setAttribute('image_id', 'img-centos-mcafee')->save();

        dispatch(new InstallSoftware($this->task));

        Event::assertNotDispatched(JobFailed::class);

        $this->task->refresh();

        $this->assertTrue(isset($this->task->data['instance_software_ids']));
        $this->assertEquals(1, count($this->task->data['instance_software_ids']));
    }


    public function testOptionaloftwareInstalls()
    {

    }
}
