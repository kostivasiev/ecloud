<?php

namespace Jobs\VpnService\Nsx;

use App\Jobs\VpnService\Nsx\UndeployCheck;
use App\Models\V2\Task;
use App\Models\V2\VpnService;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;
use function dispatch;
use function factory;

class UndeployCheckTest extends TestCase
{
    use VpnServiceMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnService());
            $this->task->save();
        });
    }

    public function testNotFoundSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services?include_mark_for_delete_objects=true'
            ])
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
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id .
                '/locale-services/' . $this->vpnService->router->id .
                '/ipsec-vpn-services?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $this->vpnService->id
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
