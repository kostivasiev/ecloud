<?php

namespace App\Console\Commands\Router;

use App\Models\V2\Router;
use App\Tasks\Vpc\CreateManagementInfrastructure;
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
        $deployed = 0;

        if ($this->option('test-run')) {
            $this->info('==== TEST MODE ====');
        }
        $routers = ($this->option('router')) ?
            Router::isManagement()->where('id', '=', $this->option('router'))->get():
            Router::isManagement()->get();
        $routers->each(function (Router $router) use (&$deployed) {
            $deploy = false;

            if ($router->networks()->count() < 1) {
                $this->info('Management router ' . $router->id . ' has no management network. Re-deploying management infrastructure...');
                $deploy = true;
            }
            if ($router->firewallPolicies()->count() < 1) {
                $this->info('Management router ' . $router->id . ' has no firewall policy. Re-deploying management infrastructure...');
                $deploy = true;
            }

            if ($router->vpc->advanced_networking != false) {
                if ($router->networks()->count() > 0 && !$router->networks()->first()->networkPolicy()->exists()) {
                    $this->info('Management router ' . $router->id . ' has no network policy. Re-deploying management infrastructure...');
                    $deploy = true;
                }
            }

            if ($deploy) {
                if (!$this->option('test-run')) {
                    $task = $router->vpc->createTask(
                        CreateManagementInfrastructure::$name,
                        CreateManagementInfrastructure::class,
                        [
                            'availability_zone_id' => $router->availability_zone_id
                        ]
                    );
                    $this->info('Task ID: ' . $task->id);
                }
                $deployed++;
            }
        });

        $this->info($deployed . ' Management infrastructure updated');

        return Command::SUCCESS;
    }
}
