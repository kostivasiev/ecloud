<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\PowerOn;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerOnTest extends TestCase
{
    public function testPowerOnJob()
    {
        $this->kingpinServiceMock()->expects('post')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instance()->id . '/power'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new PowerOn($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
