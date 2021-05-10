<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\DeleteNics;
use App\Models\V2\Instance;
use App\Models\V2\Nic;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
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
            $this->instance = factory(Instance::class)->create([
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
        Model::withoutEvents(function() {
            $this->instance = factory(Instance::class)->create([
                'id' => 'i-test',
            ]);
            $this->nic = $this->instance->nics()->create([
                'id' => 'vol-test',
                'mac_address' => 'aa:bb:cc:dd:ee:ff',
                'network_id' => 'net-test',
            ]);
        });

        Event::fake();

        dispatch(new DeleteNics($this->instance));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        $this->nic->refresh();
        $this->assertNotNull($this->nic->deleted_at);
    }
}
