<?php

namespace Tests\Unit\Jobs\Network;

use App\Jobs\Network\AwaitPortRemoval;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class AwaitPortRemovalTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testSuccessWhenNoVIFPortsFound()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'unique_id' => '77bcbe70-4619-47f8-ac25-d2bd4217f603',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/api/v1/logical-ports?logical_switch_id=77bcbe70-4619-47f8-ac25-d2bd4217f603'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'attachment' => [
                                'attachment_type' => 'DHCP',
                            ],
                        ]
                    ],
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitPortRemoval($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSuccessWhenNetworkDoesntExit()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        Event::fake([JobFailed::class]);

        dispatch(new AwaitPortRemoval($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenVIFPortsExist()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'unique_id' => '77bcbe70-4619-47f8-ac25-d2bd4217f603',
                ]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['/api/v1/logical-ports?logical_switch_id=77bcbe70-4619-47f8-ac25-d2bd4217f603'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'attachment' => [
                                'attachment_type' => 'VIF',
                            ],
                        ]
                    ],
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitPortRemoval($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
