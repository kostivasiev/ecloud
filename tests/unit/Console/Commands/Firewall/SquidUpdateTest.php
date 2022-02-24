<?php

namespace Tests\unit\Console\Commands\Firewall;

use App\Console\Commands\Firewall\SquidUpdate;
use App\Events\V2\Task\Created;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class SquidUpdateTest extends TestCase
{
    protected $mock;

    public FirewallRule $firewallRule;
    public FirewallRulePort $firewallRulePort;

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

    public function testHandleGetManagementFirewallPolicyFails()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->allows('getManagementFirewallPolicy')->withAnyArgs()->andReturnFalse();
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : Management firewall policy not present.', $message);
    }

    public function testHandleGetEdRuleFails()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('error')->with(\Mockery::capture($message));
        $this->mock->allows('getEdRule')->withAnyArgs()->andReturnFalse();
        $this->mock->handle();

        $this->assertEquals(
            $this->router()->id . ' : Firewall rule for Ed is not present in policy ' .
            $this->firewallPolicy()->id,
            $message
        );
    }

    public function testHandleGetSquidPortsPasses()
    {
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('getSquidPorts')->withAnyArgs()->andReturn($this->firewallRule);
        $this->mock->handle();

        $this->assertEquals($this->router()->id . ' : Squid rule already present.', $message);
    }

    public function testHandleGetSquidPortsFails()
    {
        $task = $this->createSyncUpdateTask($this->firewallPolicy());
        $this->mock->allows('option')->with('router')->andReturn($this->router()->id);
        $this->mock->allows('info')->with(\Mockery::capture($message));
        $this->mock->allows('getSquidPorts')->withAnyArgs()->andReturnFalse();
        $this->mock->allows('addSquidPorts')->withAnyArgs();
        $this->mock->allows('updateFirewallRuleAndSyncPolicy')
            ->withAnyArgs()
            ->andReturn($task->id);
        $this->mock->handle();

        $this->assertEquals(
            'Firewall rule ' . $this->firewallRule->id . ' updated, sync task ' . $task->id . ' created',
            $message
        );
    }
}
