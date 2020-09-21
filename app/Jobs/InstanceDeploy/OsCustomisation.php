<?php

namespace App\Jobs\InstanceDeploy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class OsCustomisation extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/331
     */
    public function handle()
    {
        Log::info('OsCustomisation');
    }
}
