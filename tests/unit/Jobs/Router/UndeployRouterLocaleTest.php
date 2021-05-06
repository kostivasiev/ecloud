<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\UndeployRouterLocale;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployRouterLocaleTest extends TestCase
{
    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testRemovesRouterLocaleWhenExists()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new UndeployRouterLocale($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDoesntRemoveRouterLocaleWhenNotExist()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );
        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new UndeployRouterLocale($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
