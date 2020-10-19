<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTaskJobChild extends Job
{
    public function handle()
    {
        Log::info('TestTaskJobChild: Handling TestTaskJobChild');
        $this->fail(new \Exception("test exception"));
        // throw new \Exception("test exception");
    }
}
