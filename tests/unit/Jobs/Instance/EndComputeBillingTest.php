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
        $this->instance()->setAttribute('is_online', false)->saveQuietly();
        Event::fake([JobFailed::class]);
        dispatch(new EndComputeBilling($this->instance()));
        Event::assertNotDispatched(JobFailed::class);
    }
}
