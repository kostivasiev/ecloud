<?php
namespace App\Jobs\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class AttachToInstance extends Job
{
    private $volume;
    private $instance;

    public function __construct(Volume $volume, Instance $instance)
    {
        $this->volume = $volume;
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started');

        if ($this->instance->volumes()->get()->count() < 15) {
            $this->instance->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/'.$this->instance->vpc_id.'/instance/'.$this->instance->id.'/volume/attach',
                [
                    'json' => [
                        'volumeUUID' => $this->volume->vmware_uuid,
                        'shared' => true,
                        'unitNumber' => 0
                    ]
                ]
            );
            Log::info('Volume '.$this->volume->id.' has been attached to instance '.$this->instance->id);

            // Once attached set the iops
            $this->instance->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/'.$this->instance->vpc_id.'/instance/'.$this->instance->id.'/volume/'.$this->volume->vmware_uuid.'/iops',
                [
                    'json' => [
                        'limit' => $this->volume->iops
                    ]
                ]
            );
            Log::info('Volume '.$this->volume->id.' iops has been set to '.$this->volume->iops);
        }

        $this->volume->setSyncCompleted();
        Log::info(get_class($this) . ' : Finished');
    }
}