<?php

namespace Tests\Unit\Jobs\Nic;

use App\Events\V2\Task\Created;
use App\Jobs\Nic\Undeploy;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    public function testKingpinErrorFails()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->kingpinServiceMock()
            ->expects('delete')
            ->andThrow(
                new RequestException('Server Error', new Request('DELETE', 'test'), new Response(500))
            );

        $task = $this->createSyncUpdateTask($this->nic());

        $this->expectException(RequestException::class);

        dispatch(new Undeploy($task));

        Event::assertDispatched(JobProcessed::class);

        Event::assertDispatched(JobFailed::class);
    }

    public function testNicNotFoundSucceeds()
    {
        Event::fake([JobFailed::class, Created::class, JobProcessed::class]);

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->nic()->instance->id .
                '/nic/AA:BB:CC:DD:EE:FF'
            ])
            ->andThrow(new RequestException('Not Found', new Request('DELETE', 'test'), new Response(404)));

        $task = $this->createSyncDeleteTask($this->nic());

        dispatch(new Undeploy($task));

        Event::assertDispatched(JobProcessed::class);

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        Event::fake([JobFailed::class]);

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs([
                '/api/v2/vpc/' . $this->vpc()->id .
                '/instance/' . $this->nic()->instance->id .
                '/nic/AA:BB:CC:DD:EE:FF'
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $task = $this->createSyncdeleteTask($this->nic());

        dispatch(new Undeploy($task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
