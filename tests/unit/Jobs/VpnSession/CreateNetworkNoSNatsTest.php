<?php

namespace Tests\unit\Jobs\VpnSession;

use App\Events\V2\Credential\Creating;
use App\Events\V2\Task\Created;
use App\Jobs\VpnSession\CreateNetworkNoSNats;
use App\Jobs\VpnSession\CreatePreSharedKey;
use App\Models\V2\Credential;
use App\Models\V2\Nat;
use App\Models\V2\VpnSession;
use App\Models\V2\VpnSessionNetwork;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\Mocks\Resources\VpnSessionMock;
use Tests\TestCase;

class CreateNetworkNoSNatsTest extends TestCase
{
    use VpnSessionMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->vpnSession();
    }

    public function testSingleLocalAndRemoteNetworksOneNoSNATRuleCreated()
    {
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateNetworkNoSNats($this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')->get();
        $this->assertEquals(1, $natCollection->count());
        $nat = $natCollection->first();
        $this->assertEquals('vpnsn-testremote1', $nat->destination_id);
        $this->assertEquals(Nat::ACTION_NOSNAT, $nat->action);
    }

    public function testSingleLocalAndMultipleRemoteNetworksMultipleNoSNATRuleCreated()
    {
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.3',
        ]);

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateNetworkNoSNats($this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')->get();
        $this->assertEquals(2, $natCollection->count());

        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());
    }

    public function testMultipleLocalAndMultipleRemoteNetworksMultipleNoSNATRuleCreated()
    {
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal1',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '1.1.1.1',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testlocal2',
            'type' => VpnSessionNetwork::TYPE_LOCAL,
            'ip_address' => '8.8.8.8',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote1',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '2.2.2.2',
        ]);
        $this->vpnSession->vpnSessionNetworks()->create([
            'id' => 'vpnsn-testremote2',
            'type' => VpnSessionNetwork::TYPE_REMOTE,
            'ip_address' => '3.3.3.3',
        ]);

        Event::fake([JobFailed::class, Created::class]);

        dispatch(new CreateNetworkNoSNats($this->vpnSession));

        $natCollection = Nat::where('source_id', '=', 'vpnsn-testlocal1')
                            ->orWhere('source_id', '=', 'vpnsn-testlocal2')->get();

        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal1' && $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal1' && $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal2' && $nat->destination_id == 'vpnsn-testremote1';
        })->isEmpty());
        $this->assertTrue(!$natCollection->filter(function($nat) {
            return $nat->source_id == 'vpnsn-testlocal2' && $nat->destination_id == 'vpnsn-testremote2';
        })->isEmpty());

        $this->assertEquals(4, $natCollection->count());
    }
}