<?php

namespace Tests\unit\Jobs\LoadBalancerNetwork;

use App\Jobs\LoadBalancerNetwork\CreateNics;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\LoadBalancerMock;
use Tests\TestCase;

class CreateNicsTest extends TestCase
{
    use LoadBalancerMock;

    public function testNicDoesNotExistCreates()
    {
        Event::fake([JobFailed::class]);

        $this->loadBalancerInstance();

        dispatch(new CreateNics($this->createSyncUpdateTask($this->loadBalancerNetwork())));

        Event::assertNotDispatched(JobFailed::class);
    }

}
