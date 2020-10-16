<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTaskJob extends TaskJob
{
    public function handle()
    {
        Log::info('TestTaskJob: Handling TestTaskJob');
    }
}
