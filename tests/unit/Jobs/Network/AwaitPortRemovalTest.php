<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\AwaitPortRemoval;
use App\Jobs\Network\Deploy;
use App\Jobs\Network\Undeploy;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class AwaitPortRemovalTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSuccessWhenNoPortsFound()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/ports?include_mark_for_delete_objects=true'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 0,
                    'results' => []
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new AwaitPortRemoval($this->network()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSuccessWhenNetworkDoesntExit()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        Event::fake([JobFailed::class]);

        dispatch(new AwaitPortRemoval($this->network()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testJobReleasedWhenPortsExist()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/ports?include_mark_for_delete_objects=true'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'result_count' => 1,
                    'results' => []
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new AwaitPortRemoval($this->network()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
