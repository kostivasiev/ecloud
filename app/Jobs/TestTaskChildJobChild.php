<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTaskChildJobChild extends TaskJob
{
    public function handle()
    {
        Log::info('TestTaskChildJobChild: Handling TestTaskChildJobChild');
        $this->fail(new \Exception("test exception"));
        // throw new \Exception("test exception");
    }
}
