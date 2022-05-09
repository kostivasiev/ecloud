<?php

namespace App\Console\Commands\Firewall;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\Network;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use App\Console\Commands\Command;

class SquidUpdate extends Command
{
    protected $signature = 'firewall:add-squid-port {--D|debug} {--T|test-run} {--router=}';
    protected $description = 'Fixes the Admin Router T0 values';

    public function handle()
    {
        $routers = ($this->option('router')) ?
            Router::where([
                ['id', '=', $this->option('router')],
                ['is_management', '=', true]
            ])->get():
            Router::where('is_management', '=', true)->get();

        $routers->each(function ($router) {
            $this->info('---');
            $this->info('Processing router ' . $router->id . ' (' . $router->name . ')');
            $this->info('---');
            $this->processFirewalls($router);
            $this->processNetworks($router);
        });
    }

    public function processFirewalls(Router $router)
    {
        // 1. Get Firewall Policy
        $firewallPolicy = $this->getManagementFirewallPolicy($router);
        if (!$firewallPolicy) {
            $this->error('Error: ' . $router->id . ' : Management firewall policy not present.');
            return;
        }
        $this->info('Processing fwp ' . $firewallPolicy->id . ' (' . $firewallPolicy->name . ')');

        // 2. Get Allow Ed rule
        $firewallRule = $this->getEdRule($router, $firewallPolicy);
        if (!$firewallRule) {
            $this->error(
                'Error: ' . $router->id . ' : Firewall rule for Ed is not present in policy ' . $firewallPolicy->id
            );
            return;
        }

        // 3. Check if squid port is present, if not create it
        $firewallRulePort = $this->getSquidPorts($firewallRule);
        if (!$firewallRulePort) {
            $this->addSquidPorts($firewallRule);
            $taskId = $this->updateFirewallRuleAndSyncPolicy($router, $firewallPolicy, $firewallRule);
            $this->info('Firewall rule ' . $firewallRule->id . ' updated, sync task ' . $taskId . ' created');
            return;
        }
        $this->info($router->id . ' : Squid rule already present for firewall.');
    }

    public function processNetworks(Router $router)
    {
        if ($router->vpc->advanced_networking) {
            $networkPolicy = NetworkPolicy::whereHas('network', function ($query) use ($router) {
                $query->where('router_id', '=', $router->id);
            })->first();
            if (!$networkPolicy) {
                $this->error('Error: No network policy found for router ' . $router->id);
                return false;
            }

            $this->info('Processing networkPolicy ' . $networkPolicy->id . ' (' . $networkPolicy->name . ')');

            $networkRule = $networkPolicy->networkRules()->where([
                ['name', 'like', 'Allow_Ed_%_outbound_' . $networkPolicy->network_id]
            ])->first();
            if (!$networkRule) {
                $this->error('Error: No Ed proxy rule found for network ' . $networkPolicy->network_id);
                return false;
            }
            $squidPort = $networkRule->networkRulePorts()->where([
                ['protocol', '=', 'TCP'],
                ['destination', '=', '3128']
            ])->get();
            if ($squidPort->count() <= 0) {
                $this->info(
                    'Creating Squid Port rule for router ' . $router->id .
                    ' on network ' . $networkPolicy->network_id
                );
                if ($this->option('test-run')) {
                    return true;
                }
                $networkRulePort = new NetworkRulePort([
                    'network_rule_id' => $networkRule->id,
                    'protocol' => 'TCP',
                    'source' => 'ANY',
                    'destination' => '3128'
                ]);
                $networkRulePort->save();

                // Update the network rule name
                $this->info('Updating name for network rule ' . $networkRule->id);
                $networkRule->name = 'Allow_Ed_Proxy_outbound_' . $networkPolicy->network_id;
                $networkRule->save();

                $this->info('Syncing policy ' . $networkPolicy->id);
                $networkPolicy->syncSave();
                return true;
            }
            $this->info($router->id . ' : Squid rule already present for network ' . $networkPolicy->network_id);
            return true;
        }
        $this->info('Router ' . $router->id . ' is not connected to an advanced networking vpc');
    }

    public function getManagementFirewallPolicy(Router $router)
    {
        if ($router->firewallPolicies()->count() === 0) {
            $this->error('Error: ' . $router->id . ' : has no firewall policies.');
            return false;
        }
        $policies = $router->firewallPolicies()
            ->where('name', '=', 'Management_Firewall_Policy_for_' . $router->id)
            ->get();
        if ($policies->count() <= 0) {
            $this->error('Error: No management firewall policy found for router ' . $router->id);
            return false;
        }
        return $policies->first();
    }

    public function getEdRule(Router $router, FirewallPolicy $firewallPolicy)
    {
        if ($firewallPolicy->firewallRules()->count() === 0) {
            $this->error('Error: ' . $router->id . ' : Firewall Policy ' . $firewallPolicy->id . ' has no rules.');
            return false;
        }
        $rules = $firewallPolicy->firewallRules()
            ->where('name', 'like', 'Allow_Ed_%_outbound_' . $router->id)
            ->get();
        if ($rules->count() <= 0) {
            $this->error('Error: No outbound Ed rule found for policy ' . $firewallPolicy->id);
            return false;
        }
        return $rules->first();
    }

    public function getSquidPorts($firewallRule)
    {
        if ($firewallRule->firewallRulePorts()->count() === 0) {
            $this->info($firewallRule->id . ' : has no firewall ports.');
            return false;
        }
        $ports = $firewallRule->firewallRulePorts()->where('destination', '=', '3128')->get();
        if ($ports->count() <= 0) {
            $this->info('No squid rule found for ' . $firewallRule->id);
            return false;
        }
        return $ports->first();
    }

    public function addSquidPorts($firewallRule)
    {
        $this->info('Adding Firewall Rule Port to ' . $firewallRule->id);
        if ($this->option('test-run')) {
            return true;
        }
        $firewallRulePort = new FirewallRulePort([
            'firewall_rule_id' => $firewallRule->id,
            'protocol' => 'TCP',
            'source' => 'ANY',
            'destination' => '3128',
        ]);
        $firewallRulePort->save();
        return $firewallRulePort;
    }

    public function updateFirewallRuleAndSyncPolicy($router, $firewallPolicy, $firewallRule)
    {
        $this->info('Updating Firewall Rule ' . $firewallRule->id);
        if ($this->option('test-run')) {
            return 'task-test-run';
        }
        $firewallRule->name = 'Allow_Ed_Proxy_outbound_' . $router->id;
        $firewallRule->save();
        $task = $firewallPolicy->syncSave();
        return $task->id;
    }
}
