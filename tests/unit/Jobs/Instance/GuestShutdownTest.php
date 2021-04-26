<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\GuestShutdown;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class GuestShutdownTest extends TestCase
{
    use DatabaseMigrations;

    public function testGuestShutdownJob()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power/guest/shutdown'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new GuestShutdown($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}