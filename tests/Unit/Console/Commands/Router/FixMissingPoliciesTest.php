<?php

namespace Tests\Unit\Console\Commands\Router;

use App\Console\Commands\Router\FixMissingPolicies;
use App\Events\V2\Task\Created;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class FixMissingPoliciesTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->command = \Mockery::mock(FixMissingPolicies::class)
            ->makePartial();
        $this->router()->setAttribute('is_management', true)->saveQuietly();
        $this->network();
    }

    public function testFixFirewallPolicies()
    {
        Event::fake([Created::class]);
        $this->command
            ->allows('info')
            ->withAnyArgs()
            ->andReturnTrue();
        $this->command->allows('option')->with('test-run')->andReturnFalse();
        $this->command->fixFirewallPolicies($this->router());

        $this->router()->refresh();

        Event::assertDispatched(Created::class);

        $firewallPolicies = $this->router()->firewallPolicies()->get();
        $firewallRules = $firewallPolicies->first()->firewallRules()->get();
        $firewallRulePorts = $firewallRules->first()->firewallRulePorts()->get();
        $this->assertEquals(1, $firewallPolicies->count());
        $this->assertEquals(1, $firewallRules->count());
        $this->assertEquals(2, $firewallRulePorts->count());
    }

    public function testFixFirewallHasPolicy()
    {
        $this->command
            ->allows('info')
            ->with(\Mockery::capture($message))
            ->andReturnTrue();
        $this->firewallPolicy();

        $this->command->fixFirewallPolicies($this->router());

        $this->assertEquals(
            sprintf(
                'Firewall Policy present for %s, skipping.',
                $this->router()->id
            ),
            $message
        );
    }

    public function testFixNetworkPolicies()
    {
        Event::fake([Created::class]);

        $this->command
            ->allows('info')
            ->withAnyArgs()
            ->andReturnTrue();

        $this->command->allows('option')
            ->with('test-run')
            ->andReturnFalse();

        $this->command->fixNetworkPolicies($this->router());

        $this->router()->refresh();

        Event::assertDispatched(Created::class);

        $networkPolicy = $this->router()->networks()->first()->networkPolicy;
        $this->assertNotNull($networkPolicy);

        $networkRules = $networkPolicy->networkRules()->first();
        $networkRulePorts = $networkRules->networkRulePorts()->get();

        $this->assertEquals(1, $networkRules->count());
        $this->assertEquals(2, $networkRulePorts->count());
    }

    public function testFixNetworkHasPolicy()
    {
        $this->command
            ->allows('info')
            ->with(\Mockery::capture($message))
            ->andReturnTrue();

        $this->networkPolicy();

        $this->command->fixNetworkPolicies($this->router());

        $this->assertEquals(
            sprintf(
                'Network Policy present for %s, skipping.',
                $this->router()->id
            ),
            $message
        );
    }

    public function testHandleFixAll()
    {
        Event::fake([Created::class]);
        $this->command->allows('option')
            ->with('router')
            ->andReturnFalse();
        $this->command->allows('option')
            ->with('test-run')
            ->andReturnFalse();

        $this->command
            ->allows('info')
            ->with(\Mockery::capture($message))
            ->andReturnTrue();
        $this->command->allows('option')->with('test-run')->andReturnFalse();

        $this->command->handle();


        $this->router()->refresh();

        Event::assertDispatched(Created::class);

        $firewallPolicies = $this->router()->firewallPolicies()->get();
        $firewallRules = $firewallPolicies->first()->firewallRules()->get();
        $firewallRulePorts = $firewallRules->first()->firewallRulePorts()->get();

        $this->assertEquals(1, $firewallPolicies->count());
        $this->assertEquals(1, $firewallRules->count());
        $this->assertEquals(2, $firewallRulePorts->count());

        $networkPolicy = $this->router()->networks()->first()->networkPolicy;
        $this->assertNotNull($networkPolicy);

        $networkRules = $networkPolicy->networkRules()->first();
        $networkRulePorts = $networkRules->networkRulePorts()->get();

        $this->assertEquals(1, $networkRules->count());
        $this->assertEquals(2, $networkRulePorts->count());
    }

    public function testHandleDoesNothingWithNonManagement()
    {
        $this->router()->setAttribute('is_management', false)->saveQuietly();
        $this->command->allows('option')
            ->with('router')
            ->andReturn($this->router()->id);
        $this->command->allows('option')
            ->with('test-run')
            ->andReturnFalse();

        $this->assertEquals(0, $this->command->handle());
    }
}
