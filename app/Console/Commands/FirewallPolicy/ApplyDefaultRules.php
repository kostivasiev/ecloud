<?php

namespace App\Console\Commands\FirewallPolicy;

use App\Models\V2\FirewallPolicy;
use App\Models\V2\Router;
use Illuminate\Console\Command;

class ApplyDefaultRules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'firewall-policy:missing-defaults {--T|test-run} {--router=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Applies missing default rules to System policies';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if ($this->option('router')) {
            $router = Router::find($this->option('router'));
            if (!$router) {
                $this->info('Router `'. $this->option('router') . '` not found.');
                return 1;
            }
            $firewallPolicies = $router->firewallPolicies()->systemPolicy()->get();
        } else {
            $firewallPolicies = FirewallPolicy::systemPolicy()->get();
        }

        if ($firewallPolicies->count() <= 0) {
            $this->info('No System Policies found.');
            return 1;
        }

        $defaultRules = config('firewall.system.rules');

        foreach ($firewallPolicies as $firewallPolicy) {
            if ($firewallPolicy->firewallRules()->count() > 0) {
                $this->info('System policy ' . $firewallPolicy->id . ' already has rules present');
                continue;
            }
            if (!$this->option('test-run')) {
                $firewallPolicy->createRulesAndPorts($defaultRules);
                $firewallPolicy->syncSave();
            }
        }

        return 0;
    }
}
