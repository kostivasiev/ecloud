<?php

namespace Tests\unit\Console\Commands\Firewall;

use App\Console\Commands\Firewall\SquidUpdate;
use App\Events\V2\Task\Created;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SquidUpdateTest extends TestCase
{
    protected $mock;

    public FirewallRule $firewallRule;
    public FirewallRulePort $firewallRulePort;
    public NetworkRule $networkRule;
    public NetworkRulePort $networkRulePort;

    public function setUp(): void
    {
        parent::setUp();
        $this->mock = \Mockery::mock(SquidUpdate::class)->makePartial();
        $this->router()->setAttribute('is_management', 1)->saveQuietly();
        $this->firewallPolicy()
            ->setAttribute('name', 'Management_Firewall_Policy_for_' . $this->router()->id)
            ->saveQuietly();
        $this->firewallRule = factory(FirewallRule::class)->create([
            'id' => 'fwr-test',
            'name' => 'Allow_Ed_on_Port_4222_outbound_' . $this->router()->id,
            'sequence' => 10,
            'firewall_policy_id' => $this->firewallPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'OUT',
            'enabled' => true
        ]);
        $this->firewallRule->saveQuietly();
        $this->firewallRulePort = new FirewallRulePort([
            'id' => 'fwp-test',
            'name' => 'fwp-test',
            'firewall_rule_id' => $this->firewallRule->id,
            'protocol' => 'TCP',
            'source' => 'ANY',
            'destination' => '3128'
        ]);
        $this->firewallRulePort->saveQuietly();
        $this->vpc()->setAttribute('advanced_networking', true)->saveQuietly();
        $this->networkRule = factory(NetworkRule::class)->create([
            'name' => 'Allow_Ed_on_Port_4222_outbound_' . $this->network()->id,
            'sequence' => 10,
            'network_policy_id' => $this->networkPolicy()->id,
            'source' => 'ANY',
            'destination' => 'ANY',
            'action' => 'ALLOW',
            'direction' => 'OUT',
            'enabled' => true
        ]);
        $this->networkRulePort = factory(NetworkRulePort::class)->create([
            'network_rule_id' => $this->networkRule->id,
            'protocol' => 'TCP',
            'source' => 'ANY',
            'destination' => '3128'
        ]);
    }

    public function testGetManagementFirewallPolicyNoPolicies()
    {
        Model::withoutEvents(function () {
            $this->firewallPolicy()->delete();
        });
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getManagementFirewallPolicy($this->router()));

        $this->assertEquals($this->router()->id . ' : has no firewall policies.', $message);
    }

    public function testGetManagementFirewallPolicyNoManagementPolicy()
    {
        $this->firewallPolicy()->setAttribute('name', 'Not Management')->saveQuietly();
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getManagementFirewallPolicy($this->router()));
        $this->assertEquals('No management firewall policy found for router ' . $this->router()->id, $message);
    }

    public function testGetManagementFirewallPolicy()
    {
        $this->assertEquals(
            $this->firewallPolicy()->id,
            ($this->mock->getManagementFirewallPolicy($this->router()))->id
        );
    }

    public function testGetEdRuleNoRules()
    {
        Model::withoutEvents(function () {
            $this->firewallRule->delete();
        });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->getEdRule($this->router(), $this->firewallPolicy()));

        $this->assertEquals(
            $this->router()->id . ' : Firewall Policy ' . $this->firewallPolicy()->id . ' has no rules.',
            $message
        );
    }

    public function testGetEdRuleNoEdRules()
    {
        $this->firewallRule->setAttribute('name', 'Not an Ed rule')->saveQuietly();
        $this->mock->allows('error')->with(\Mockery::capture($message));

        $this->assertFalse($this->mock->getEdRule($this->router(), $this->firewallPolicy()));

        $this->assertEquals(
            'No outbound Ed rule found for policy ' . $this->firewallPolicy()->id,
            $message
        );
    }

    public function testGetEdRule()
    {
        $this->assertEquals(
            $this->firewallRule->id,
            ($this->mock->getEdRule($this->router(), $this->firewallPolicy()))->id
        );
    }

    public function testGetSquidPortsNoPorts()
    {
        Model::withoutEvents(function () {
            $this->firewallRulePort->delete();
        });
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->getSquidPorts($this->firewallRule));
        $this->assertEquals($this->firewallRule->id . ' : has no firewall ports.', $message);
    }

    public function testGetSquidPortsZeroResults()
    {
        Model::withoutEvents(function () {
            $this->firewallRulePort
                ->setAttribute('destination', '3306')
                ->saveQuietly();
        });
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->assertFalse($this->mock->getSquidPorts($this->firewallRule));
        $this->assertEquals('No squid rule found for ' . $this->firewallRule->id, $message);
    }

    public function testGetSquidPorts()
    {
        $this->assertEquals(
            $this->firewallRulePort->id,
            ($this->mock->getSquidPorts($this->firewallRule))->id
        );
    }

    public function testAddSquidPorts()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        $firewallRulePort = $this->mock->addSquidPorts($this->firewallRule);
        $this->assertEquals('3128', $firewallRulePort->destination);
        $this->assertEquals($this->firewallRule->id, $firewallRulePort->firewall_rule_id);
    }

    public function testUpdateFirewallRuleAndSyncPolicy()
    {
        $this->mock->allows('info')->withAnyArgs();
        $this->mock->allows('option')->with('test-run')->andReturnFalse();
        Event::fake(Created::class);
        $this->mock->updateFirewallRuleAndSyncPolicy($this->router(), $this->firewallPolicy(), $this->firewallRule);
        Event::assertDispatched(Created::class);
    }

    public function testProcessFirewallsGetManagementFirewallPolicyFails()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->allows('getManagementFirewallPolicy')->withAnyArgs()->andReturnFalse();
        $this->mock->processFirewalls($this->router());

        $this->assertEquals($this->router()->id . ' : Management firewall policy not present.', $message);
    }

    public function testProcessFirewallsGetEdRuleFails()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->allows('getEdRule')->withAnyArgs()->andReturnFalse();
        $this->mock->processFirewalls($this->router());

        $this->assertEquals(
            $this->router()->id . ' : Firewall rule for Ed is not present in policy ' .
            $this->firewallPolicy()->id,
            $message
        );
    }

    public function testProcessFirewallsGetSquidPortsPasses()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('getSquidPorts')->withAnyArgs()->andReturn($this->firewallRule);
        $this->mock->processFirewalls($this->router());

        $this->assertEquals($this->router()->id . ' : Squid rule already present for firewall.', $message);
    }

    public function testProcessFirewallsGetSquidPortsFails()
    {
        $task = $this->createSyncUpdateTask($this->firewallPolicy());
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('getSquidPorts')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('addSquidPorts')->withAnyArgs();
        $this->mock->allows('updateFirewallRuleAndSyncPolicy')
            ->withAnyArgs()
            ->andReturn($task->id);
        $this->mock->processFirewalls($this->router());

        $this->assertEquals(
            'Firewall rule ' . $this->firewallRule->id . ' updated, sync task ' . $task->id . ' created',
            $message
        );
    }

    public function testProcessNetworksNoAdvancedNetworking()
    {
        Model::withoutEvents(function () {
            $this->vpc()->setAttribute('advanced_networking', false)->saveQuietly();
        });
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->processNetworks($this->router());

        $this->assertEquals(
            'Router ' . $this->router()->id . ' is not connected to an advanced networking vpc',
            $message
        );
    }

    public function testProcessNetworksNoNetworkPolicy()
    {
        Model::withoutEvents(function () {
            $this->networkPolicy()->delete();
        });
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->processNetworks($this->router());

        $this->assertEquals('No network policy found for router ' . $this->router()->id, $message);
    }

    public function testProcessNetworksNoNetworkRule()
    {
        $this->networkRule->delete();
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->processNetworks($this->router());

        $this->assertEquals('No Ed proxy rule found for network ' . $this->network()->id, $message);
    }

    public function testProcessNetworksRulePresent()
    {
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->processNetworks($this->router());

        $this->assertEquals(
            $this->router()->id . ' : Squid rule already present for network ' . $this->network()->id,
            $message
        );
    }

    public function testProcessNetworksRuleNotPresent()
    {
        $this->networkRulePort->delete();
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('option')->with('test-run')->andReturnFalse();

        Event::fake(Created::class);
        $this->mock->processNetworks($this->router());
        Event::assertDispatched(Created::class);

        $this->networkRule->refresh();
        $this->assertEquals('Syncing policy ' . $this->networkPolicy()->id, $message);
        $this->assertEquals('Allow_Ed_Proxy_outbound_' . $this->network()->id, $this->networkRule->name);
    }
}
