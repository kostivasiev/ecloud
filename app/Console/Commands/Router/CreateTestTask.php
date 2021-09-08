<?php

namespace App\Console\Commands\Router;

use App\Events\V2\Router\Creating;
use App\Listeners\V2\Router\DefaultRouterThroughput;
use App\Models\V2\Router;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CreateTestTask extends Command
{
    protected $signature = 'router:create-test-task';

    protected $description = 'Creates a test task for #998';

    public function handle()
    {
        $router = Router::all()->first();
        $task = $router->createTaskWithLock('test_task', \App\Jobs\Tasks\TestTask::class);

        return Command::SUCCESS;
    }
}
