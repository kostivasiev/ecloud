<?php

namespace Tests\Unit\Jobs\Instance;

use App\Jobs\Instance\MigrateToHostGroup;
use App\Models\V2\AvailabilityZone;
use App\Models\V2\HostGroup;
use App\Models\V2\Task;
use App\Tasks\Instance\Migrate;
use Database\Seeders\ResourceTierSeeder;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class MigrateToHostGroupTest extends TestCase
{
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        Task::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'test-task',
                'name' => Migrate::$name,
                'job' => Migrate::class,
                'data' => [
                    'host_group_id' => $this->hostGroup()->id,
                ]
            ]);
            $this->task->resource()->associate($this->instanceModel());
            $this->task->save();
        });
    }

    public function testMigrateSuccess()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->hostGroup()->id,
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new MigrateToHostGroup($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testAlreadyInHostGroupSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->instanceModel()->host_group_id = $this->hostGroup()->id;
        $this->instanceModel()->saveQuietly();

        $this->kingpinServiceMock()->expects('post')
            ->withArgs([
                '/api/v2/vpc/vpc-test/instance/i-test/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->hostGroup()->id,
                    ]
                ]
            ])->never();

        dispatch(new MigrateToHostGroup($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Event::assertNotDispatched(JobFailed::class);
    }
}
