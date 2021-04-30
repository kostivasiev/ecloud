<?php

namespace Tests\unit\Jobs\Nsx\NetworkPolicy;

use App\Jobs\Nsx\DeployCheck;
use App\Models\V2\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployCheckTest extends TestCase
{
    use DatabaseMigrations;

    protected Sync $sync;

    public function testFirewallPolicyRealizedNotReleasedAndSucceeds()
    {
        Model::withoutEvents(function() {
            $this->sync = new Sync([
                'id' => 'sync-1',
                'completed' => true,
                'type' => Sync::TYPE_UPDATE
            ]);
            $this->sync->resource()->associate($this->networkPolicy());
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
            $this->sync->resource,
            $this->sync->resource->network->router->availabilityZone,
            '/infra/domains/default/security-policies/'
        ));

        Event::assertNotDispatched(JobFailed::class);

        Event::assertDispatched(JobProcessed::class, function ($event) {
            return !$event->job->isReleased();
        });
    }
}
