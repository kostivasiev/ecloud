<?php

namespace App\Console\Commands\Rules;

use App\Models\V2\FirewallRule;
use App\Models\V2\NetworkRule;
use Illuminate\Console\Command;

class ModifyEdRules extends Command
{
    protected $signature = 'rules:update-ed-rules {--D|debug} {--T|test-run}';
    protected $description = 'Update Firewall and Netword Ed rules';

    public function handle()
    {
        $firewallRuleCount = 0;
        $networkRuleCount = 0;
        FirewallRule::where([
            ['name', 'LIKE', 'Allow_Ed_on_Port_4222_inbound_%'],
            ['direction', '=', 'IN'],
        ])->each(function (FirewallRule $firewallRule) use (&$firewallRuleCount) {
            if ($this->option('debug')) {
                $this->info('Modifying firewall rule ' . $firewallRule->id);
            }

            if (!$this->option('test-run')) {
                $firewallRule = $this->changeRule($firewallRule);
                $firewallRule->firewallPolicy->syncSave();
            }
            $firewallRuleCount++;
        });

        NetworkRule::where([
            ['name', 'LIKE', 'Allow_Ed_on_Port_4222_inbound_%'],
            ['direction', '=', 'IN'],
        ])->each(function (NetworkRule $networkRule) use (&$networkRuleCount) {
            if ($this->option('debug')) {
                $this->info('Modifying network rule ' . $networkRule->id);
            }

            if (!$this->option('test-run')) {
                $networkRule = $this->changeRule($networkRule);
                $networkRule->networkPolicy->syncSave();
            }
            $networkRuleCount++;
        });

        $this->info(PHP_EOL . 'Firewall rules modified: ' . $firewallRuleCount);
        $this->info('Network rules modified: ' . $networkRuleCount . PHP_EOL);
    }

    protected function changeRule($rule)
    {
        $rule->name = str_replace('inbound', 'outbound', $rule->name);
        $rule->direction = 'OUT';
        $rule->save();
        return $rule;
    }
}