<?php

namespace Tests\unit\Jobs\VpnService\Nsx;

use App\Jobs\VpnService\Nsx\DeployCheck;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnServiceMock;
use Tests\TestCase;
use function dispatch;

class DeployCheckTest extends TestCase
{
    use VpnServiceMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnService());
            $this->task->save();
        });
    }

    public function testVPnServiceRealizedNotReleasedAndSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path='  .
                '/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' .
                $this->vpnService()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'REALIZED'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }

    public function testVPnServiceNotRealizedReleased()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path='  .
                '/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' .
                $this->vpnService()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'invalid'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck($this->task));

        Event::assertNotDispatched(JobFailed::class);
        Event::assertDispatched(JobProcessed::class, function ($event) {
            return $event->job->isReleased();
        });
    }
}
