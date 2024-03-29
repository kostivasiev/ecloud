<?php

namespace Tests\Unit\Jobs\Instance\Deploy;

use App\Events\V2\Task\Created;
use App\Jobs\Instance\Deploy\CreateFloatingIp;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class CreateFloatingIpTest extends TestCase
{
    public function testRequiresFloatingIpFalseSkips()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new CreateFloatingIp($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testNoNicsFails()
    {
        Event::fake([JobFailed::class]);

        $instance = Instance::withoutEvents(function() {
            return Instance::factory()->create([
                'id' => 'i-test',
                'vpc_id' => $this->vpc()->id,
                'deploy_data' => [
                    'requires_floating_ip' => true,
                ]
            ]);
        });

        dispatch(new CreateFloatingIp($instance));

        Event::assertDispatched(JobFailed::class);
    }

    public function testSuccess()
    {
        $this->nic();

        // Bind and return test ID on creation
        app()->bind(FloatingIp::class, function () {
            return FloatingIp::factory()->make([
                'id' => 'fip-test',
            ]);
        });

        $deploy_data = $this->instanceModel()->deploy_data;
        $deploy_data['requires_floating_ip'] = true;
        $this->instanceModel()->deploy_data = $deploy_data;
        $this->instanceModel()->save();

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateFloatingIp($this->instanceModel()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'sync_update';
        });

        $this->instanceModel()->refresh();

        $this->assertEquals('fip-test', $this->instanceModel()->deploy_data['floating_ip_id']);
    }
}