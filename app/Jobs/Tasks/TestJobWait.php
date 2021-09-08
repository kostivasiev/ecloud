<?php

namespace App\Jobs\Tasks;

use App\Jobs\Job;
use App\Models\V2\Host;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class TestJobWait extends Job
{
    use Batchable;

    //public $tries = 180;
    //public $backoff = 5;

    public function __construct()
    {
    }

    public function handle()
    {
        for ($i=0; $i<10; $i++) {
            Log::warning("TestJobWait loop iteration [".$i."]");
            // Sleep for 5 seconds, 180 times (15 minutes total), less than the 20 minutes configured for redis queue connection retry_after
            sleep(5);
        }
    }
}
