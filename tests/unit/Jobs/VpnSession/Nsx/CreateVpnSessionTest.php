<?php
namespace Jobs\VpnSession\Nsx;

use App\Jobs\Nsx\VpnSession\CreateVpnSession;
use App\Models\V2\Credential;
use App\Models\V2\Task;
use App\Models\V2\VpnSessionNetwork;
use App\Support\Sync;
use GuzzleHttp\Psr7\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;
use function dispatch;
use function factory;

class CreateVpnSessionTest extends TestCase
{
    use VpnSessionMock;

    protected Task $task;

    public function setUp(): void
    {
        parent::setUp();

        Model::withoutEvents(function () {
            $this->task = new Task([
                'id' => 'sync-1',
                'name' => Sync::TASK_NAME_UPDATE,
            ]);
            $this->task->resource()->associate($this->vpnSession());
            $this->task->save();
        });
    }

    public function testSucess()
    {
        Event::fake([JobFailed::class]);

        Credential::withoutEvents(function () {
            $credential = factory(Credential::class)->create([
                'id' => 'cred-test',
                'name' => 'Pre-shared Key for VPN Session ' . $this->vpnSession()->id,
                'host' => null,
                'username' => 'PSK',
                'password' => Str::random(32),
                'port' => null,
                'is_hidden' => false,
            ]);
            $this->vpnSession()->credentials()->save($credential);
        });

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
                [
                    'json' => [
                        'resource_type' => 'PolicyBasedIPSecVpnSession',
                        'authentication_mode' => 'PSK',
                        'psk' => $this->vpnSession()->psk,
                        'display_name' => $this->vpnSession()->id,
                        'ike_profile_path' => '/infra/ipsec-vpn-ike-profiles/' . $this->vpnProfileGroup()->ike_profile_id,
                        'tunnel_profile_path' => '/infra/ipsec-vpn-tunnel-profiles/' . $this->vpnProfileGroup()->ipsec_profile_id,
                        'local_endpoint_path' => '/infra/tier-1s/' . $this->router()->id .'/locale-services/' . $this->router()->id . '/ipsec-vpn-services/' . $this->vpnService()->id . '/local-endpoints/' . $this->vpnEndpoint()->id,
                        'peer_address' => $this->vpnSession()->remote_ip,
                        'peer_id' => $this->vpnSession()->remote_ip,
                        'rules' => [
                            [
                                'resource_type' => 'IPSecVpnRule',
                                'id' => $this->vpnSession()->id . '-custom-rule-1',
                                'sources' => [],
                                'destinations' => []
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new CreateVpnSession($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNoPskFails()
    {
        Event::fake([JobFailed::class]);

        dispatch(new CreateVpnSession($this->task));

        Event::assertDispatched(JobFailed::class);
    }

    public function testNetworkAddressUsedWhenHostSpecifiedWithinNetworkSubnets()
    {
        Event::fake([JobFailed::class]);

        Credential::withoutEvents(function () {
            $credential = factory(Credential::class)->create([
                'id' => 'cred-test',
                'name' => 'Pre-shared Key for VPN Session ' . $this->vpnSession()->id,
                'host' => null,
                'username' => 'PSK',
                'password' => Str::random(32),
                'port' => null,
                'is_hidden' => false,
            ]);
            $this->vpnSession()->credentials()->save($credential);
        });

        $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1/24',
        ]);
        $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2/24',
        ]);

        $this->nsxServiceMock()->shouldReceive('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/' . $this->router()->id .
                '/locale-services/' . $this->router()->id .
                '/ipsec-vpn-services/' . $this->vpnService()->id .
                '/sessions/' . $this->vpnSession()->id,
                [
                    'json' => [
                        'resource_type' => 'PolicyBasedIPSecVpnSession',
                        'authentication_mode' => 'PSK',
                        'psk' => $this->vpnSession()->psk,
                        'display_name' => $this->vpnSession()->id,
                        'ike_profile_path' => '/infra/ipsec-vpn-ike-profiles/' . $this->vpnProfileGroup()->ike_profile_id,
                        'tunnel_profile_path' => '/infra/ipsec-vpn-tunnel-profiles/' . $this->vpnProfileGroup()->ipsec_profile_id,
                        'local_endpoint_path' => '/infra/tier-1s/' . $this->router()->id .'/locale-services/' . $this->router()->id . '/ipsec-vpn-services/' . $this->vpnService()->id . '/local-endpoints/' . $this->vpnEndpoint()->id,
                        'peer_address' => $this->vpnSession()->remote_ip,
                        'peer_id' => $this->vpnSession()->remote_ip,
                        'rules' => [
                            [
                                'resource_type' => 'IPSecVpnRule',
                                'id' => $this->vpnSession()->id . '-custom-rule-1',
                                'sources' => [
                                    [
                                        "subnet" => "1.1.1.0/24"
                                    ]
                                ],
                                'destinations' => [
                                    [
                                        "subnet" => "2.2.2.0/24"
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200);
            });

        dispatch(new CreateVpnSession($this->task));

        Event::assertNotDispatched(JobFailed::class);
    }
}