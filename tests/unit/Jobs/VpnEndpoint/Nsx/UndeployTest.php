<?php

namespace Jobs\VpnEndpoint\Nsx;

use App\Jobs\VpnEndpoint\Nsx\Undeploy;
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

class UndeployTest extends TestCase
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

    public function testSuccess()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' . $this->vpnEndpoint()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testFailure()
    {
        $this->expectException(RequestException::class);
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/local-endpoints/' . $this->vpnEndpoint()->id
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
