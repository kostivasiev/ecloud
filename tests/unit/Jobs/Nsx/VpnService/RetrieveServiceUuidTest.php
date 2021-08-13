<?php
namespace Tests\unit\Jobs\Nsx\VpnService;

use App\Jobs\Nsx\VpnService\RetrieveEndpointUuid;
use App\Models\V2\VpnService;
use GuzzleHttp\Psr7\Response;
use Tests\TestCase;

class RetrieveServiceUuidTest extends TestCase
{
    public VpnService $vpnService;
    public $job;

    public function setUp(): void
    {
        parent::setUp();
        $this->vpnService = factory(VpnService::class)->create([
            'id' => 'vpn-uuidtest',
            'name' => 'Unit Test VPN',
            'router_id' => $this->router()->id,
        ]);
        $this->job = \Mockery::mock(RetrieveEndpointUuid::class, [$this->vpnService])
            ->makePartial();
    }

    public function testJobRetry()
    {
        $this->job->shouldReceive('release')
            ->with(\Mockery::capture($backoffValue));

        $this->nsxServiceMock()->shouldReceive('get')
            ->withSomeOfArgs(
                '/api/v1/search/query?query=resource_type:IPSecVPNService%20AND%20display_name:vpn-uuidtest'
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
                '/api/v1/search/query?query=resource_type:IPSecVPNService%20AND%20display_name:vpn-uuidtest'
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
        $this->assertEquals($uuid, $this->vpnService->nsx_uuid);
    }
}