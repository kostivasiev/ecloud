<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTaskJob extends ResourceTaskJob
{
    public function handle()
    {
        Log::info('TestTaskJob: Handling TestTaskJob');
    }
}
