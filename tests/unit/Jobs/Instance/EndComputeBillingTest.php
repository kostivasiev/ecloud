<?php

namespace Tests\unit\Jobs\Instance;

use App\Jobs\Instance\EndComputeBilling;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EndComputeBillingTest extends TestCase
{

    public function testEndComputeBillingJob()
    {
        $this->kingpinServiceMock()->expects('get')
            ->withArgs(['/api/v2/vpc/' . $this->instance()->vpc_id . '/instance/' . $this->instance()->id])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'powerState' => 'poweredOff',
                    'toolsRunningStatus' => 'guestToolsRunning',
                ]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new EndComputeBilling($this->instance()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
