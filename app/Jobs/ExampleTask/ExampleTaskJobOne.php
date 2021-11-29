<?php

namespace App\Jobs\ExampleTask;

use App\Jobs\TaskJob;

class ExampleTaskJobOne extends TaskJob
{
    public function handle()
    {
        $this->info("indicating the job is starting");
        sleep(5);
        $this->info("indicating the job is finishing");
    }
}