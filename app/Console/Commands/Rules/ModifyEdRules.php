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
            $firewallRulePort = $firewallRule->firewallRulePorts->first();

            if (strpos($firewallRule->firewallPolicy->router->name, "Management") === false) {
                $this->error("NOT MANAGEMENT! ABORT! " . $firewallRule->firewallPolicy->router->name);
                return;
            }
            
            if ($this->option('debug')) {
                $this->info('> Processing rule for router ' . $firewallRule->firewallPolicy->router->id . ' with name "' . $firewallRule->firewallPolicy->router->name.'"');
                $this->info('Modifying firewall rule ' . $firewallRule->id);
                $this->info('Modifying firewall rule port '.$firewallRulePort->id . ' for network rule ' . $firewallRulePort->firewall_rule_id);
            }

            if (!$this->option('test-run')) {
                $firewallRule = $this->changeRule($firewallRule);
                $firewallRulePort = $this->changeRulePort($firewallRulePort);

                $firewallRule->firewallPolicy->syncSave();
            }
            $firewallRuleCount++;
        });

        NetworkRule::where([
            ['name', 'LIKE', 'Allow_Ed_on_Port_4222_inbound_%'],
            ['direction', '=', 'IN'],
        ])->each(function (NetworkRule $networkRule) use (&$networkRuleCount) {
            $networkRulePort = $networkRule->networkRulePorts->first();

            if (strpos($networkRule->networkPolicy->network->name, "Management") === false) {
                $this->error("NOT MANAGEMENT! ABORT! " . $networkRule->networkPolicy->network->name);
                return;
            }

            if ($this->option('debug')) {
                $this->info('> Processing rule for network ' . $networkRule->networkPolicy->network->id . ' with name "' . $networkRule->networkPolicy->network->name.'"');
                $this->info('Modifying network rule ' . $networkRule->id);
                $this->info('Modifying network rule port '.$networkRulePort->id .' for network rule '.$networkRulePort->network_rule_id);
            }

            if (!$this->option('test-run')) {
                $networkRule = $this->changeRule($networkRule);
                $networkRulePort = $this->changeRulePort($networkRulePort);

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

    protected function changeRulePort($rulePort)
    {
        $rulePort->source = 'ANY';
        $rulePort->save();
        return $rulePort;
    }
}
