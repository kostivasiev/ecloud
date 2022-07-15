<?php

namespace Tests\Unit\Jobs\Instance\Migrate;

use App\Jobs\Instance\Migrate\PowerOn;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Tasks\Instance\Migrate;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use App\Jobs\Instance\PowerOn as InstancePowerOn;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class PowerOnTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
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

    public function testDifferentHostSpecPowersOn()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);
        Bus::fake([InstancePowerOn::class]);

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_INSTANCE_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDOFF,
                ]));
            });

        dispatch(new PowerOn($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Bus::assertDispatched(InstancePowerOn::class);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSameHostSpecDoesNotPowerOff()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);
        Bus::fake([InstancePowerOn::class]);

        dispatch(new PowerOn($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Bus::assertNotDispatched(InstancePowerOn::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
