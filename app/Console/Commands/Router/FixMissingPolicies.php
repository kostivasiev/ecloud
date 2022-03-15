<?php

namespace App\Console\Commands\Router;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\FirewallRule;
use App\Models\V2\FirewallRulePort;
use App\Models\V2\NetworkPolicy;
use App\Models\V2\NetworkRule;
use App\Models\V2\NetworkRulePort;
use App\Models\V2\Router;
use Illuminate\Console\Command;

class FixMissingPolicies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'router:fix-missing-policies {--T|test-run} {--router=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds missing firewall and network policies to management resources';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('test-run')) {
            $this->info('==== TEST MODE START ====');
        }
        $routers = ($this->option('router')) ?
            Router::isManagement()->where('id', '=', $this->option('router'))->get():
            Router::isManagement()->get();
        $routers->each(function (Router $router) {
            $this->fixFirewallPolicies($router)
                ->fixNetworkPolicies($router);
        });
        if ($this->option('test-run')) {
            $this->info('==== TEST MODE END ====');
        }
        return 0;
    }

    public function fixFirewallPolicies(Router $router): self
    {
        if ($router->firewallPolicies->count() > 0) {
            $this->info('Firewall Policy present for ' . $router->id . ', skipping.');
            return $this;
        }

        $this->info(
            'Creating default Management Firewall Policy and Rules for ' . $router->id
        );

        if (!$this->option('test-run')) {
            $firewallPolicy = FirewallPolicy::create([
                'name' => 'Management_Firewall_Policy_for_' . $router->id,
                'router_id' => $router->id,
                'sequence' => 0,
            ]);

            // Allow outbound 4222 and 3128
            $firewallRule = FirewallRule::create([
                'name' => 'Allow_Ed_Proxy_outbound_' . $router->id,
                'sequence' => 10,
                'firewall_policy_id' => $firewallPolicy->id,
                'source' => 'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true
            ]);

            FirewallRulePort::create([
                'firewall_rule_id' => $firewallRule->id,
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '4222'
            ]);

            FirewallRulePort::create([
                'firewall_rule_id' => $firewallRule->id,
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '3128'
            ]);

            $firewallPolicy->syncSave();
        }

        $this->info('Firewall Policy and Rules for ' . $router->id . ' created.');

        return $this;
    }

    public function fixNetworkPolicies(Router $router)
    {
        $network = $router->networks->first();
        if ($network->networkPolicy) {
            $this->info('Network Policy present for ' . $router->id . ', skipping.');
            return;
        }

        $this->info(
            'Creating default Management Network Policy and Rules for ' . $router->id
        );

        if (!$this->option('test-run')) {
            $networkPolicy = NetworkPolicy::create([
                'name' => 'Management_Network_Policy_for_' . $network->id,
                'network_id' => $network->id,
            ]);

            // Allow inbound 4222
            $networkRule = NetworkRule::create([
                'name' => 'Allow_Ed_Proxy_outbound_' . $network->id,
                'sequence' => 10,
                'network_policy_id' => $networkPolicy->id,
                'source' => 'ANY',
                'destination' => 'ANY',
                'action' => 'ALLOW',
                'direction' => 'OUT',
                'enabled' => true
            ]);

            NetworkRulePort::create([
                'network_rule_id' => $networkRule->id,
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '4222'
            ]);

            NetworkRulePort::create([
                'network_rule_id' => $networkRule->id,
                'protocol' => 'TCP',
                'source' => 'ANY',
                'destination' => '3128'
            ]);

            $networkPolicy->syncSave();
        }

        $this->info('Network Policy and Rules for ' . $router->id . ' created.');
    }
}
