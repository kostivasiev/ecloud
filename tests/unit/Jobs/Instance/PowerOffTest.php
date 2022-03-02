<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\PowerOff;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PowerOffTest extends TestCase
{
    public function testPowerOffJob()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        $this->kingpinServiceMock()->expects('delete')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new PowerOff($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testPowerOffJobInstanceDoesNotExist()
    {
        $this->expectException(RequestException::class);

        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        $job = new PowerOff($this->instanceModel());
        $job->handle();
    }

    public function testIgnoreInstanceNotFound()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id])
            ->andThrow(
                new RequestException('Not Found', new Request('GET', 'test'), new Response(404))
            );

        Event::fake([JobFailed::class]);

        $job = new PowerOff($this->instanceModel(), true);

        $job->handle();

        Event::assertNotDispatched(JobFailed::class);
    }
}
