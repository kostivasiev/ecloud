<?php

namespace App\Jobs\InstanceDeploy;

use App\Jobs\Job;
use Illuminate\Support\Facades\Log;

class Deploy extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/325
     */
    public function handle()
    {
        // - Call Kingpin to create instance
        // - Create Volumes / NIC's from Kinpin
        // - Send created Volume ID's to Kinpin using their UUID's (Keep in this job or?)

        // Code to create volumes with...
//        $volume = Volume::withoutEvents(function () use ($instance) {
//            $volume = new Volume();
//            $volume::addCustomKey($volume);
//            $volume->name = $volume->id;
//            $volume->vpc()->associate($instance->vpc);
//            $volume->save();
//            return $volume;
//        });

        Log::info('Deploy');
    }
}
