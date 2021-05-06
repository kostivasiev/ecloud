<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\DeployCheck;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployCheckTest extends TestCase
{
    protected Task $task;

    public function testFirewallPolicyRealizedNotReleasedAndSucceeds()
    {
        Model::withoutEvents(function() {
            $this->task = new Task([
                'id' => 'task-1',
                'completed' => true,
                'name' => Sync::TASK_NAME_UPDATE
            ]);
            $this->task->resource()->associate($this->networkPolicy());
        });

        $this->nsxServiceMock()->expects('get')
            ->withArgs([
                'policy/api/v1/infra/realized-state/status?intent_path=/infra/domains/default/security-policies/' . $this->networkPolicy()->id
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'publish_status' => 'REALIZED'
                ]));
            });

        Event::fake([JobFailed::class, JobProcessed::class]);

        dispatch(new DeployCheck(
            $this->task->resource,
            $this->task->resource->network->router->availabilityZone,
            '/infra/domains/default/security-policies/'
        ));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
