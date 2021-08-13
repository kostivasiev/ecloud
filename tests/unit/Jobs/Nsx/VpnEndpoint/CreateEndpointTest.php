<?php
namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\Nsx\VpnEndpoint\CreateEndpoint;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateEndpointTest extends TestCase
{
    protected VpnService $vpnService;
    protected VpnEndpoint $vpnEndpoint;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpnService = VpnService::withoutEvents(function () {
            return factory(VpnService::class)->create([
                'id' => 'vpn-unittest',
                'router_id' => $this->router()->id,
            ]);
        });
        $this->vpnEndpoint = VpnEndpoint::withoutEvents(function () {
            return factory(VpnEndpoint::class)->create([
                'id' => 'vpne-unittest',
                'vpn_service_id' => $this->vpnService->id,
            ]);
        });
    }

    public function testSuccessful()
    {
        Event::fake([Created::class]);
        $this->nsxServiceMock()->shouldReceive('post')
            ->withSomeOfArgs('/api/v1/vpn/ipsec/local-endpoints')
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

        dispatch(new CreateEndpoint($task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailure()
    {
        $this->expectException(RequestException::class);
        Event::fake([Created::class]);
        $this->nsxServiceMock()->shouldReceive('post')
            ->withSomeOfArgs('/api/v1/vpn/ipsec/local-endpoints')
            ->andThrow(new RequestException('Not Found', new Request('post', '/'), new Response(404, [], 'Resource not found')));
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

        dispatch(new CreateEndpoint($task));

        Event::assertDispatched(JobFailed::class);
    }
}