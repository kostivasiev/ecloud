<?php

namespace Tests\unit\Jobs\Vpc\Defaults;

use App\Jobs\Vpc\Defaults\AwaitRouterSync;
use App\Models\V2\FloatingIp;
use App\Models\V2\Nat;
use App\Models\V2\Nic;
use App\Models\V2\Router;
use App\Models\V2\Sync;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitRouterSyncTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testJobSucceedsWhenSyncComplete()
    {
        Model::withoutEvents(function () {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
            ]);
            $sync->resource()->associate($this->router());
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitRouterSync($this->router()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobFailedWhenSyncFailed()
    {
        Model::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
                'failure_reason' => 'test',
            ]);
            $sync->resource()->associate($this->router());
            $sync->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitRouterSync($this->router()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenSyncInProgress()
    {
        Model::withoutEvents(function() {
            $sync = new Sync([
                'id' => 'sync-1',
                'completed' => false,
            ]);
            $sync->resource()->associate($this->router());
            $sync->save();
        });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitRouterSync($this->router()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
