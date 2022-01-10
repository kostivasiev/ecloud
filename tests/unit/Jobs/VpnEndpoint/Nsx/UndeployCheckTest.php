<?php

namespace Jobs\VpnEndpoint\Nsx;

use App\Jobs\VpnEndpoint\Nsx\UndeployCheck;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;
use function dispatch;

class UndeployCheckTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });
    }

    public function testNotFoundSucceeds()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [],
                ]));
            });

        dispatch(new UndeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testJobReleasedWhenStillExists()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints?include_mark_for_delete_objects=true'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $this->vpnEndpoint()->id
                        ],
                    ],
                ]));
            });

        dispatch(new UndeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
