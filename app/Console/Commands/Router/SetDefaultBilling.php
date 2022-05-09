<?php

namespace App\Console\Commands\Router;

use App\Events\V2\Router\Creating;
use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\Router;
use App\Console\Commands\Command;
use Illuminate\Support\Facades\Log;

class SetDefaultBilling extends Command
{
    protected $signature = 'router:set-default-billing';

    protected $description = 'Updates existing routers that dont have router throughput and set to the default value';

    public function handle()
    {
        $listener = new DefaultRouterThroughput();
        Router::all()->each(function ($router) use ($listener) {
            try {
                $this->info('Running default billing listener against ' . $router->id);
                $event = new Creating($router);
                $listener->handle($event);
                $event->model->save();
            } catch (\Throwable $exception) {
                Log::error('Failed to set default router throughput', [
                    'router_id' => $router->id,
                    'exception' => $exception,
                ]);
            }
        });

        return Command::SUCCESS;
    }
}
