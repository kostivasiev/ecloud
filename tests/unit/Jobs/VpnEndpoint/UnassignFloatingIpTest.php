<?php

namespace Tests\unit\Jobs\VpnEndpoint;

use App\Events\V2\Task\Created;
use App\Jobs\VpnEndpoint\UnassignFloatingIP;
use App\Models\V2\Task;
use App\Support\Sync;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnEndpointMock;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;

class UnassignFloatingIpTest extends TestCase
{
    use VpnServiceMock, VpnEndpointMock;

    public function testNoFloatingIpAssignedSucceeds()
    {
        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint('vpne-test', false)));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testFloatingIpUnassignJobIsDispatched()
    {
        Event::fake([JobProcessed::class, Created::class]);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint()));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(Created::class, function ($event) {
            return $event->model->name == 'floating_ip_unassign';
        });
    }

    public function testUnassignFloatingIpFailedFails()
    {
        Event::fake([JobProcessed::class, Created::class, JobFailed::class]);

        $task = new Task([
            'id' => 'task-test',
            'completed' => false,
            'failure_reason' => 'Test Failure',
            'name' => Sync::TASK_NAME_UPDATE,
        ]);
        $this->vpnEndpoint()->floatingIp->tasks()->save($task);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint()));

        Event::assertDispatched(JobFailed::class);
    }

    public function testAwaitFloatingIpSyncIsReleased()
    {
        Event::fake([JobProcessed::class, Created::class]);

        $this->vpnEndpoint();

        $task = new Task([
            'id' => 'task-test',
            'completed' => false,
            'name' => 'floating_ip_unassign',
        ]);
        $this->vpnEndpoint()->floatingIp->tasks()->save($task);

        dispatch(new UnassignFloatingIP($this->vpnEndpoint()));

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
