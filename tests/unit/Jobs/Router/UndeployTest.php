<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\Undeploy;
use App\Jobs\Router\UndeployRouterLocale;
use App\Models\V2\Router;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use DatabaseMigrations;

    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRemovesRouterWhenExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntRemoveRouterWhenNotExist()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );
        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
