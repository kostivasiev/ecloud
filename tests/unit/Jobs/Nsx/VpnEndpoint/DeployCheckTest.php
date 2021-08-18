<?php

namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Nsx\DeployCheck;
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

class DeployCheckTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    protected Task $task;

    public function testVpnEndpointRealizedNotReleasedAndSucceeds()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path='  .
                '/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' .
                $this->vpnEndpoint()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'REALIZED'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck(
            $this->task->resource,
            $this->availabilityZone(),
            '/infra/tier-1s/' . $this->task->resource->vpnService->router->id .
            '/locale-services/' . $this->task->resource->vpnService->router->id .
            '/ipsec-vpn-services/' . $this->task->resource->vpnService->id .
            '/local-endpoints/'
        ));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testVpnEndpointNotRealizedReleased()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path='  .
                '/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' .
                $this->vpnEndpoint()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'invalid'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck(
            $this->task->resource,
            $this->availabilityZone(),
            '/infra/tier-1s/' . $this->task->resource->vpnService->router->id .
            '/locale-services/' . $this->task->resource->vpnService->router->id .
            '/ipsec-vpn-services/' . $this->task->resource->vpnService->id .
            '/local-endpoints/'
        ));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
