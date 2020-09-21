<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class PowerOn extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/328
     */
    public function handle()
    {
        Log::info('PowerOn');
    }
}
