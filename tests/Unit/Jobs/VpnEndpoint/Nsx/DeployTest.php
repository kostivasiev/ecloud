<?php

namespace Tests\Unit\Jobs\VpnEndpoint\Nsx;

use App\Events\V2\Task\Created;
use App\Jobs\VpnEndpoint\Nsx\Deploy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;
use function dispatch;

class DeployTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    protected Task $task;

    public function testSuccessful()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        Event::fake([Created::class]);
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id . '/local-endpoints/' . $this->vpnEndpoint()->id,
                [
                    'json' => [
                        'resource_type' => 'IPSecVpnLocalEndpoint',
                        'display_name' => $this->vpnEndpoint()->id,
                        'description' => $this->vpnEndpoint()->name,
                        'local_id' => $this->vpnEndpoint()->floatingIp->ip_address,
                        'local_address' => $this->vpnEndpoint()->floatingIp->ip_address
                    ]
                ]
            ])
            ->andReturnTrue();

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoFloatingIpFails()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint('vpne-test', false));
            $this->task->save();
        });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNoRouterFails()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        Event::fake([JobFailed::class]);

        $this->vpnService()->router_id = 'rtr-xxx';
        $this->vpnService()->save();

        dispatch(new Deploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNSXFailureFails()
    {
        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnEndpoint());
            $this->task->save();
        });

        $this->expectException(RequestException::class);
        Event::fake([JobFailed::class]);
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id . '/local-endpoints/' . $this->vpnEndpoint()->id,
                [
                    'json' => [
                        'resource_type' => 'IPSecVpnLocalEndpoint',
                        'display_name' => $this->vpnEndpoint()->id,
                        'description' => $this->vpnEndpoint()->name,
                        'local_id' => $this->vpnEndpoint()->floatingIp->ip_address,
                        'local_address' => $this->vpnEndpoint()->floatingIp->ip_address
                    ]
                ]
            ])
            ->andThrow(new RequestException('Not Found', new Request('post', '/'), new Response(404, [], 'Resource not found')));

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}