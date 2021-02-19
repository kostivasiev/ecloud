<?php
namespace App\Jobs\Kingpin\Volume;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Volume;
use GuzzleHttp\Exception\ServerException;
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

        if ($this->instance->volumes()->get()->count() < config('volume.instance.limit', 15)) {
            try {
                $this->instance->availabilityZone->kingpinService()->post(
                    '/api/v2/vpc/'.$this->instance->vpc_id.'/instance/'.$this->instance->id.'/volume/attach',
                    [
                        'json' => [
                            'volumeUUID' => $this->volume->vmware_uuid
                        ]
                    ]
                );
            } catch (ServerException $exception) {
                Log::error($exception->getResponse()->getBody()->getContents());
                throw $exception;
            }
            Log::info('Volume '.$this->volume->id.' has been attached to instance '.$this->instance->id);

            // Once attached set the iops
            try {
                $this->instance->availabilityZone->kingpinService()->put(
                    '/api/v2/vpc/'.$this->instance->vpc_id.'/instance/'.$this->instance->id.'/volume/'.$this->volume->vmware_uuid.'/iops',
                    [
                        'json' => [
                            'limit' => $this->volume->iops
                        ]
                    ]
                );
            } catch (ServerException $exception) {
                Log::error($exception->getResponse()->getBody()->getContents());
                throw $exception;
            }
            Log::info('Volume '.$this->volume->id.' iops has been set to '.$this->volume->iops);
        }

        $this->volume->setSyncCompleted();
        Log::info(get_class($this) . ' : Finished');
    }
}
