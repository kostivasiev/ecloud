<?php

namespace Tests\Unit\Jobs\Instance\Migrate;

use App\Jobs\Instance\Migrate\PowerOff;
use App\Models\V2\HostSpec;
use App\Models\V2\Task;
use App\Services\V2\KingpinService;
use App\Tasks\Instance\Migrate;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use App\Jobs\Instance\PowerOff as InstancePowerOff;
use Tests\TestCase;

class PowerOffTest extends TestCase
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

        $this->kingpinServiceMock()
            ->allows('get')
            ->withSomeOfArgs(
                sprintf(KingpinService::GET_INSTANCE_URI, $this->vpc()->id, $this->instanceModel()->id)
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'powerState' => KingpinService::INSTANCE_POWERSTATE_POWEREDON,
                ]));
            });
    }

    public function testDifferentHostSpecPowersOff()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        Bus::fake([InstancePowerOff::class]);

        $this->sharedHostGroup()->hostSpec()->associate(HostSpec::factory()->create([
            'id' => 'hs-2',
        ]))->save();

        dispatch(new PowerOff($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Bus::assertDispatched(InstancePowerOff::class);

        Event::assertNotDispatched(JobFailed::class);

        $this->task->refresh();

        $this->assertTrue($this->task->data['requires_power_cycle']);
    }

    public function testSameHostSpecDoesNotPowerOff()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        Bus::fake([InstancePowerOff::class]);

        dispatch(new PowerOff($this->task));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });

        Bus::assertNotDispatched(InstancePowerOff::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
