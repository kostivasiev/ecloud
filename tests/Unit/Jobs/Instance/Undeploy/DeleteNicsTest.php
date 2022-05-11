<?php

namespace Tests\Unit\Jobs\Instance\Undeploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteNicsTest extends TestCase
{
    protected Instance $instance;
    protected Nic $nic;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWithNoNics()
    {
        Model::withoutEvents(function() {
            $this->instance = Instance::factory()->create([
                'id' => 'i-test',
            ]);
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteNics($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testDeletesAttachedNics()
    {
        $this->nic();

        Event::fake([Created::class, JobFailed::class, JobProcessed::class]);

        dispatch(new DeleteNics($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertDispatched(Created::class);
    }
}
