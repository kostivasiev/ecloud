<?php

namespace App\Jobs\Kingpin\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class MoveToPublicHostGroup extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->availabilityZone->kingpinService()
            ->post(
                '/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/reschedule',
                [
                    'json' => [
                        'resourceTierTags' => config('instance.resource_tier_tags')
                    ],
                ]
            );
        Log::debug('Instance ' . $this->model->id . ' was moved to Public Host group');
    }
}
