<?php

namespace Tests\Unit\Jobs\Instance;

use App\Jobs\Instance\GuestShutdown;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GuestShutdownTest extends TestCase
{
    public function testGuestShutdownJob()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power/guest/shutdown'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new GuestShutdown($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
