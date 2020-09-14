<?php

namespace App\Jobs\InstanceDeploy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class PrepareOsUsers extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('PrepareOsUsers');
    }
}
