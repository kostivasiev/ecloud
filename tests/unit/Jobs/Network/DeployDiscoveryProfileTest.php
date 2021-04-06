<?php

namespace Tests\unit\Jobs\Network;

use App\Jobs\Network\Deploy;
use App\Jobs\Network\DeployDiscoveryProfile;
use App\Models\V2\Router;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Laravel\Lumen\Testing\DatabaseMigrations;
use Tests\TestCase;

class DeployDiscoveryProfileTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp(): void
    {
        parent::setUp();
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

        dispatch(new DeployDiscoveryProfile($this->network()));

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

        dispatch(new DeployDiscoveryProfile($this->network()));

        Event::assertNotDispatched(JobFailed::class);
    }
}
