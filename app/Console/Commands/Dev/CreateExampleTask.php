<?php

namespace App\Console\Commands\Dev;

use App\Models\V2\Instance;
use App\Tasks\ExampleTask\ExampleTask;
use App\Console\Commands\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateExampleTask extends Command
{
    protected $signature = 'dev:create-example-task';

    protected $description = 'Creates an example task';

    public function handle()
    {
        $instance = Instance::all()->first();
        $instance->createTask("sometesttask", ExampleTask::class);

        return Command::SUCCESS;
    }
}
