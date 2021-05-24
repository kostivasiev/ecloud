<?php

namespace Tests\unit\Jobs\Network;

use App\Events\V2\Task\Created;
use App\Jobs\Network\DeleteNetworkPolicy;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeleteNetworkPolicyTest extends TestCase
{
    public function testSucceeds()
    {
        Event::fake([JobFailed::class, Created::class]);

        $this->networkPolicy();

        $this->assertEquals(1, $this->network()->networkPolicy()->count());

        dispatch(new DeleteNetworkPolicy($this->network()));

        Event::assertDispatched(Created::class);

        Event::assertNotDispatched(JobFailed::class);
    }
}
