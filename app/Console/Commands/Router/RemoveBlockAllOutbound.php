<?php

namespace App\Console\Commands\Router;

use App\Models\V2\FirewallRule;
use App\Models\V2\Network;
use App\Models\V2\NetworkRule;
use App\Models\V2\Router;
use Illuminate\Console\Command;

class RemoveBlockAllOutbound extends Command
{
    protected $signature = 'router:cleanup-outbound-rules {--D|debug} {--T|test-run}';

    protected $description = 'Removes Block all abound rules that were created on Management Networks and Firewalls';

    public function handle()
    {
        $this->info('Cleaning up Network Rules' . PHP_EOL);

        $networkRuleCount = 0;
        $firewallRuleCount = 0;

        NetworkRule::whereHas('networkPolicy.network.router', function ($query) {
            $query->where([
                ['routers.is_management', '=', true],
                ['network_rules.name', 'LIKE', 'Block_all_outbound_%']
            ]);
        })->each(function (NetworkRule $networkRule) use (&$networkRuleCount) {
            if ($this->option('debug')) {
                $this->info('Network Rule: ' . $networkRule->id);
                $this->info($networkRule->toJson() . PHP_EOL);
            }
            if (!$this->option('test-run')) {
                $networkPolicy = $networkRule->networkPolicy;
                $networkRule->networkRulePorts->each(function ($port) {
                    $port->delete();
                });
                $networkRule->delete();
                $networkPolicy->syncSave();
            }
            $networkRuleCount++;
        });

        FirewallRule::whereHas('firewallPolicy.router', function ($query) {
            $query->where([
                ['routers.is_management', '=', true],
                ['firewall_rules.name', 'LIKE', 'Block_all_outbound_%']
            ]);
        })->each(function (FirewallRule $firewallRule) use (&$firewallRuleCount) {
            if ($this->option('debug')) {
                $this->info('Firewall Rule: ' . $firewallRule->id);
                $this->info($firewallRule->toJson() . PHP_EOL);
            }
            if (!$this->option('test-run')) {
                $firewallPolicy = $firewallRule->firewallPolicy;
                $firewallRule->firewallRulePorts->each(function ($port) {
                    $port->delete();
                });
                $firewallRule->delete();
                $firewallPolicy->syncSave();
            }
            $firewallRuleCount++;
        });

        $this->info(PHP_EOL . 'Network Rules Total: ' . $networkRuleCount);
        $this->info('Firewall Rules Total: ' . $firewallRuleCount . PHP_EOL);
    }
}
