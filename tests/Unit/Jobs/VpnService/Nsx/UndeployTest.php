<?php

namespace Tests\Unit\Jobs\VpnService\Nsx;

use App\Jobs\VpnService\Nsx\Undeploy;
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

class UndeployTest extends TestCase
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

    public function testSuccess()
    {
        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
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
                '/policy/api/v1/infra/tier-1s/' . $this->vpnService()->router->id .
                '/locale-services/' . $this->vpnService()->router->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
