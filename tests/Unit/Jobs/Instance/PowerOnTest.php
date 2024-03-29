<?php

namespace Tests\Unit\Jobs\Instance;

use App\Jobs\Instance\PowerOn;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PowerOnTest extends TestCase
{
    public function testPowerOnJob()
    {
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new PowerOn($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
