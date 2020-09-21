<?php

namespace App\Jobs\InstanceDeploy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class WaitOsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see Missing Issue?
     */
    public function handle()
    {
        Log::info('WaitOsCustomisation');
    }
}
