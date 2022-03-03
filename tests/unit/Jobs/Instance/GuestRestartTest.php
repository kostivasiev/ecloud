<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\GuestRestart;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GuestRestartTest extends TestCase
{
    public function testGuestRestartJob()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power/guest/restart'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new GuestRestart($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
