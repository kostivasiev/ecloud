<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;

class TestTask extends ResourceTask
{
    public function handle()
    {
        Log::info('TestTask: Handling TestTask');
    }
}
