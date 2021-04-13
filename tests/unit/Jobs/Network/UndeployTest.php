<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\Deploy;
use App\Jobs\Network\Undeploy;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class UndeployTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testNetworkRemovedWhenExists()
    {

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        $this->nsxServiceMock()->expects('delete')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->network()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNetworkNotRemovedWhenDoesntExist()
    {

        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id])
            ->andThrow(
                new ClientException('Not Found', new Request('GET', 'test'), new Response(404))
            );
        $this->nsxServiceMock()->shouldNotReceive('delete');

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->network()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
