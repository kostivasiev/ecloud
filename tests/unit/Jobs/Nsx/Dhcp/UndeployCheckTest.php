<?php

namespace Tests\unit\Jobs\Nsx\Dhcp;

use App\Jobs\Nsx\Dhcp\UndeployCheck;
use App\Models\V2\Dhcp;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployCheckTest extends TestCase
{
    protected $dhcp;
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->dhcp = Dhcp::factory()->create([
                'id' => 'dhcp-test',
                'vpc_id' => $this->vpc()->id,
                'availability_zone_id' => $this->availabilityZone()->id,
            ]);

            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->dhcp);
            $this->task->save();
        });
    }

    public function testSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenStillExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withSomeOfArgs('/policy/api/v1/infra/dhcp-server-configs/?include_mark_for_delete_objects=true')
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'dhcp-test'
                        ],
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UndeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
