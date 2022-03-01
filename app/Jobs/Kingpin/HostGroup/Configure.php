<?php

namespace App\Jobs\Kingpin\HostGroup;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class Configure extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(HostGroup $model)
    {
        $this->model = $model;
    }

    public function handle()
    {
        $hostGroup = $this->model;

        $hostGroup->availabilityZone->kingpinService()
            ->put('/api/v2/vpc/' . $hostGroup->vpc->id . '/hostgroup/' . $hostGroup->id . '/configure');
    }
}
