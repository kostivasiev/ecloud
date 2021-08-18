<?php
namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\Nsx\VpnEndpoint\Deploy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testSuccessful()
    {
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

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint);
            $task->data = [
                'floating_ip_id' => $this->floatingIp()->id,
            ];
            $task->save();
            return $task;
        });

        dispatch(new Deploy($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoFloatingIpFails()
    {
        Event::fake([JobFailed::class]);

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint('vpne-test', false));
            $task->data = [
                'floating_ip_id' => $this->floatingIp()->id,
            ];
            $task->save();
            return $task;
        });

        dispatch(new Deploy($this->vpnEndpoint()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNoRouterFails()
    {
        Event::fake([JobFailed::class]);

        $this->vpnService()->router_id = 'rtr-xxx';
        $this->vpnService()->save();

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint('vpne-test', false));
            $task->data = [
                'floating_ip_id' => $this->floatingIp()->id,
            ];
            $task->save();
            return $task;
        });

        dispatch(new Deploy($this->vpnEndpoint()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNSXFailureFails()
    {
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

        $task = Task::withoutEvents(function () {
            $task = new Task([
                'id' => 'task-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $task->resource()->associate($this->vpnEndpoint('vpne-test', false));
            $task->data = [
                'floating_ip_id' => $this->floatingIp()->id,
            ];
            $task->save();
            return $task;
        });

        dispatch(new Deploy($this->vpnEndpoint()));

        Event::assertDispatched(JobFailed::class);
    }
}