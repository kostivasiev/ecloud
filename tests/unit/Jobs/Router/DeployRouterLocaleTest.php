<?php

namespace Tests\unit\Jobs\Router;

use App\Jobs\Router\DeployRouterLocale;
use App\Models\V2\Router;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployRouterLocaleTest extends TestCase
{
    use DatabaseMigrations;

    protected Router $router;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testSucceeds()
    {

        $this->nsxServiceMock()->expects('patch')
            ->withSomeOfArgs('policy/api/v1/infra/tier-1s/' . $this->router()->id . '/locale-services/' . $this->router()->id)
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployRouterLocale($this->router()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
