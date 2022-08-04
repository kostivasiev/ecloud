<?php

namespace Tests\Unit\Jobs\Nat;

use App\Jobs\Nat\Deploy;
use App\Models\V2\IpAddress;
use App\Models\V2\Nat;
use App\Models\V2\VpnSessionNetwork;
use GuzzleHttp\Psr7\Response;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class DeployTest extends TestCase
{
    use VpnSessionMock;

    public IpAddress $ipAddress;

    public function setUp(): void
    {
        parent::setUp();

        $this->ipAddress = IpAddress::factory()->create([
            'network_id' => $this->network()->id,
            'ip_address' => '10.3.4.5'
        ]);
    }

    public function testSourceNATExpectedRequest()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($this->ipAddress);
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "SNAT",
                        "translated_network" => "1.1.1.1",
                        "enabled" => true,
                        "logging" => false,
                        "firewall_match" => "MATCH_EXTERNAL_ADDRESS",
                        "source_network" => "10.3.4.5",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDestinationNATExpectedRequest()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->destination()->associate($this->floatingIp());
        $nat->translated()->associate($this->ipAddress);
        $nat->action = NAT::ACTION_DNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "DNAT",
                        "translated_network" => "10.3.4.5",
                        "enabled" => true,
                        "logging" => false,
                        "firewall_match" => "MATCH_EXTERNAL_ADDRESS",
                        "destination_network" => "1.1.1.1",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testSourceCIDRSanitized()
    {
        $localNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1/24',
        ]);
        $remoteNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.0/24',
        ]);

        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($localNetwork1);
        $nat->destination()->associate($remoteNetwork1);
        $nat->action = NAT::ACTION_NOSNAT;
        $nat->save();

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "NO_SNAT",
                        "source_network" => "1.1.1.0/24",
                        "enabled" => true,
                        "logging" => false,
                        "destination_network" => "2.2.2.0/24",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testDestinationCIDRSanitized()
    {
        $localNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.0/24',
        ]);
        $remoteNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.1/24',
        ]);

        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($localNetwork1);
        $nat->destination()->associate($remoteNetwork1);
        $nat->action = NAT::ACTION_NOSNAT;
        $nat->save();


        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "NO_SNAT",
                        "source_network" => "1.1.1.0/24",
                        "enabled" => true,
                        "logging" => false,
                        "destination_network" => "2.2.2.0/24",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testTranslatedCIDRSanitized()
    {
        $localNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.0/24',
        ]);
        $remoteNetwork1 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.0/24',
        ]);
        $remoteNetwork2 = $this->vpnSession()->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.1/24',
        ]);

        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($localNetwork1);
        $nat->destination()->associate($remoteNetwork1);
        $nat->translated()->associate($remoteNetwork2);
        $nat->action = NAT::ACTION_NOSNAT;
        $nat->save();


        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "NO_SNAT",
                        "source_network" => "1.1.1.0/24",
                        "enabled" => true,
                        "logging" => false,
                        "destination_network" => "2.2.2.0/24",
                        "translated_network" => "3.3.3.0/24",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNATSequenceNumberSetWhenSequenceSpecified()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($this->ipAddress);
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->sequence = 10;
        $nat->save();

        $this->nsxServiceMock()->expects('patch')
            ->withArgs([
                '/policy/api/v1/infra/tier-1s/rtr-test/nat/USER/nat-rules/nat-test',
                [
                    'json' => [
                        "display_name" => "nat-test",
                        "description" => "nat-test",
                        "action" => "SNAT",
                        "translated_network" => "1.1.1.1",
                        "enabled" => true,
                        "logging" => false,
                        "sequence_number" => 10,
                        "firewall_match" => "MATCH_EXTERNAL_ADDRESS",
                        "source_network" => "10.3.4.5",
                        "tags" => $this->defaultVpcTags(),
                    ]
                ]
            ])
            ->andReturnUsing(function () {
                return new Response(200, [], json_encode([]));
            });

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertNotDispatched(JobFailed::class);
    }

    public function testNatNoSourceOrDestinationNatableFails()
    {
        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Could not find router scopable resource for source, destination or translated';
        });
    }

    public function testNatNoRouterFails()
    {
        $this->network()->router_id = '';
        $this->network()->save();

        $nat = app()->make(Nat::class);
        $nat->id = 'nat-test';
        $nat->source()->associate($this->ipAddress);
        $nat->translated()->associate($this->floatingIp());
        $nat->action = NAT::ACTION_SNAT;
        $nat->save();

        Event::fake([JobFailed::class]);

        dispatch(new Deploy($nat));

        Event::assertDispatched(JobFailed::class, function ($event) {
            return $event->exception->getMessage() == 'Nat Deploy nat-test : No Router found for resource';
        });
    }
}
