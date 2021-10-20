<?php

namespace App\Console\Commands\Router;

use App\Models\V2\Router;
use Illuminate\Console\Command;

class PopulateisManagementProperty extends Command
{
    protected $signature = 'router:populate-is-management-property';

    protected $description = 'Updates existing routers that use the is_hidden property to populate the is_management property instead';

    public function handle()
    {
        Router::all()->each(function ($router) {
            if ($router->is_hidden) {
                $router->is_management = true;
                $router->save();
                $this->line('Updated router ' . $router->id);
            }
        });

        return Command::SUCCESS;
    }
}
