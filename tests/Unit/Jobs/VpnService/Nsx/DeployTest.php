<?php

namespace Tests\Unit\Jobs\VpnService\Nsx;

use App\Events\V2\Task\Created;
use App\Jobs\VpnService\Nsx\Deploy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;
use function dispatch;

class DeployTest extends TestCase
{
    use VpnServiceMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnService());
            $this->task->save();
        });
    }

    public function testSuccessful()
    {
        Event::fake([Created::class]);
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id,
                [
                    'json' => [
                        'resource_type' => 'IPSecVpnService',
                        'enabled' => true
                    ]
                ]
            ])
            ->andReturnTrue();

        dispatch(new Deploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }


    public function testNSXFailureFails()
    {
        $this->expectException(RequestException::class);
        Event::fake([JobFailed::class]);
        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id,
                [
                    'json' => [
                        'resource_type' => 'IPSecVpnService',
                        'enabled' => true
                    ]
                ]
            ])
            ->andThrow(new RequestException('Not Found', new Request('patch', '/'), new Response(500, [], 'Test')));

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}