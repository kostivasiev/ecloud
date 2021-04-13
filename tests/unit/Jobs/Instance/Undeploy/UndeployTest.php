<?php

namespace Tests\unit\Jobs\Instance\Undeploy;

use App\Jobs\Instance\Undeploy\Undeploy;
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

    public function testUndeployJob()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testUndeployJobInstanceDoesNotExist()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        Event::fake([JobFailed::class]);

        dispatch(new Undeploy($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
