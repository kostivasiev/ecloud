<?php

namespace Tests\Unit\Jobs\VpnSession\Nsx;

use App\Jobs\VpnSession\Nsx\Undeploy;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use VpnSessionMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_DELETE,
            ]);
            $this->task->resource()->associate($this->vpnSession());
            $this->task->save();
        });
    }

    public function testDeleteWhenExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSkipsWhenDoesntExist()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andThrow(RequestException::create(new Request('GET', ''), new Response(404)));


        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDeleteWithGetException()
    {
        $this->expectException(RequestException::class);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testDeleteWithDeleteException()
    {
        $this->expectException(RequestException::class);

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
            ])
            ->andThrow(new RequestException('Bad Request', new Request('delete', '/'), new Response(400, [], 'Bad Request')));

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->task));

        Event::assertDispatched(JobFailed::class);
    }
}
