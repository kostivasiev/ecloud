<?php

namespace Tests\Unit\Jobs\Network;

use App\Jobs\Network\DeployDiscoveryProfile;
use App\Models\V2\Task;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class DeployDiscoveryProfileTest extends TestCase
{
    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->network());
            $this->task->save();
        });
    }

    public function testUseExistingBindingMapAndSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/segment-discovery-profile-binding-maps'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => [
                        [
                            'id' => 'some-existing-id',
                            'some_property' => 'some-existing-property',
                        ],
                    ]
                ]));
            });

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/segment-discovery-profile-binding-maps/some-existing-id',
                [
                    'json' => [
                        'id' => 'some-existing-id',
                        'some_property' => 'some-existing-property',
                        'ip_discovery_profile_path' => '/infra/ip-discovery-profiles/' . config('network.profiles.ip-discovery-profile'),
                        'mac_discovery_profile_path' => '/infra/mac-discovery-profiles/' . config('network.profiles.mac-discovery-profile'),
                    ],
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployDiscoveryProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testUseNewBindingMapAndSucceeds()
    {
        $this->nsxServiceMock()->expects('get')
            ->withArgs(['policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/segment-discovery-profile-binding-maps'])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([
                    'results' => []
                ]));
            });

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                'policy/api/v1/infra/tier-1s/' . $this->network()->router->id . '/segments/' . $this->network()->id . '/segment-discovery-profile-binding-maps/' . $this->network()->id . '-segment-discovery-profile-binding-maps',
                [
                    'json' => [
                        'id' => $this->network()->id . '-segment-discovery-profile-binding-maps',
                        'ip_discovery_profile_path' => '/infra/ip-discovery-profiles/' . config('network.profiles.ip-discovery-profile'),
                        'mac_discovery_profile_path' => '/infra/mac-discovery-profiles/' . config('network.profiles.mac-discovery-profile'),
                    ],
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new DeployDiscoveryProfile($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}
