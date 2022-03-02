<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\PowerReset;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class PowerResetTest extends TestCase
{
    public function testPowerResetJob()
    {
        $this->kingpinServiceMock()->expects('put')
            ->withArgs(['/api/v2/vpc/' . $this->vpc()->id . '/instance/' . $this->instanceModel()->id . '/power/reset'])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        Event::fake([JobFailed::class]);

        dispatch(new PowerReset($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
