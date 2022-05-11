<?php

namespace Tests\Unit\Console\Commands\FirewallPolicy;

use App\Console\Commands\FirewallPolicy\ApplyDefaultRules;
use App\Events\V2\Task\Created;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ApplyDefaultRulesTest extends TestCase
{
    protected $command;

    public function setUp(): void
    {
        parent::setUp();
        $this->command = \Mockery::mock(ApplyDefaultRules::class)->makePartial();
    }

    public function testRouterInvalid()
    {
        $this->command->allows('option')->with('router')->andReturn('rtr-xxxxxxxx');
        $this->command->allows('info')->with(\Mockery::capture($message));
        $this->command->handle();

        $this->assertEquals('Router `rtr-xxxxxxxx` not found.', $message);
    }

    public function testRouterNoPolicy()
    {
        $this->command->allows('option')->with('router')->andReturn($this->router()->id);
        $this->command->allows('info')->with(\Mockery::capture($message));
        $this->command->handle();

        $this->assertEquals('No System Policies found.', $message);
    }

    public function testNoFirewallPolicies()
    {
        $this->command->allows('option')->with('router')->andReturnFalse();
        $this->command->allows('info')->with(\Mockery::capture($message));
        $this->command->handle();

        $this->assertEquals('No System Policies found.', $message);
    }

    public function testApplyRules()
    {
        Event::fake([JobFailed::class, JobProcessed::class, Created::class]);

        $this->firewallPolicy()->setAttribute('name', 'System')->saveQuietly();
        $this->command->allows('option')->with('router')->andReturnFalse();
        $this->command->allows('option')->with('test-run')->andReturnFalse();
        $this->command->handle();

        Event::assertNotDispatched(JobFailed::class);

        foreach (config('firewall.system.rules') as $rule) {
            $firewallRule = $this->firewallPolicy()->firewallRules()
                ->where([
                    ['name', '=', $rule['name']],
                    ['direction', '=', $rule['direction']]
                ])->first();
            $this->assertEquals($rule['name'], $firewallRule->name);
            $this->assertEquals($rule['action'], $firewallRule->action);
            $this->assertEquals($rule['sequence'], $firewallRule->sequence);
            $this->assertEquals($rule['direction'], $firewallRule->direction);
            $this->assertEquals($rule['destination'], $firewallRule->destination);

            foreach ($rule['ports'] as $port) {
                $firewallRulePort = $firewallRule->firewallRulePorts()
                    ->where([
                        ['protocol', '=', $port['protocol']],
                        ['destination', '=', $port['destination']],
                    ])->first();
                $this->assertEquals($port['protocol'], $firewallRulePort->protocol);
                $this->assertEquals($port['source'], $firewallRulePort->source);
                $this->assertEquals($port['destination'], $firewallRulePort->destination);
            }
        }
    }
}
