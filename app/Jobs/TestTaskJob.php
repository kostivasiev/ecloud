<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTaskJob extends TaskJob
{
    public function handle()
    {
        Log::info('TestTaskJob: Handling TestTaskJob');

        Log::info("TestTaskJob: dispatching child job 1");

        $this->dispatchChildren([
            new TestTaskJobChild(),
          /*  new TestTaskJobChild(),
            new TestTaskJobChild(),
            new TestTaskJobChild(),*/
        ]);
        Log::info("TestTaskJob: finished");
    }
}
