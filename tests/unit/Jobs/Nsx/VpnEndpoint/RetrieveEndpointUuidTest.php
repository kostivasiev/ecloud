<?php
namespace Tests\unit\Jobs\Nsx\VpnEndpoint;

use App\Jobs\Nsx\VpnEndpoint\RetrieveEndpointUuid;
use App\Models\V2\Task;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class RetrieveEndpointUuidTest extends TestCase
{
    public VpnService $vpnService;
    public VpnEndpoint $vpnEndpoint;
    public $job;

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
                'id' => 'vpne-uuidtest',
                'vpn_service_id' => $this->vpnService->id,
            ]);
        });
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
        $this->job = \Mockery::mock(RetrieveEndpointUuid::class, [$task])
            ->makePartial();
    }

    public function testJobRetry()
    {
        $this->job->shouldReceive('release')
            ->with(\Mockery::capture($backoffValue));

        $this->nsxServiceMock()->shouldReceive('get')
            ->withSomeOfArgs(
                '/api/v1/search/query?query=resource_type:IPSecVPNLocalEndpoint%20AND%20display_name:vpne-uuidtest'
            )->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                ]));
            });
        $this->job->handle();
        $this->assertEquals($this->job->backoff, $backoffValue);
    }

    public function testNsxUuidIsSet()
    {
        $uuid = 'ac887ed8-e311-4503-a745-d8d53437e0fc';
        $this->nsxServiceMock()->shouldReceive('get')
            ->withSomeOfArgs(
                '/api/v1/search/query?query=resource_type:IPSecVPNLocalEndpoint%20AND%20display_name:vpne-uuidtest'
            )->andReturnUsing(function () use ($uuid) {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => $uuid,
                        ],
                    ],
                    'result_count' => 1,
                ]));
            });
        $this->job->handle();
        $this->assertEquals($uuid, $this->vpnEndpoint->nsx_uuid);
    }
}