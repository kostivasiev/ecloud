<?php

namespace App\Jobs\Volume;

use App\Jobs\Job;
use App\Models\V2\Volume;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignPort extends Job
{
    use Batchable, LoggableModelJob;

    public Volume $model;

    public function __construct(Volume $volume)
    {
        $this->model = $volume;
    }

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $volume = $this->model;
        if (empty($volume->volume_group_id)) {
            Log::info('Volume is not assigned to a volume group, skipping');
            return;
        }

        if (!empty($volume->port)) {
            Log::info('Volume already has a port assigned, skipping');
            return;
        }

        $ports = collect(range(0,config('volume-group.max_ports')))
            ->forget(config('volume-group.scsi_controller_reserved_port'));

        $usedPorts = $volume->volumeGroup->volumes
            ->pluck('port')
            ->filter(fn($value) => !is_null($value));

        $port =  $ports->diff($usedPorts)->first();

        if (is_null($port)) {
            Log::error(
                'Failed to assign volume group port to shared volume ' . $volume->id . ', no ports available',
                [
                    'id' => $volume->id,
                    'volume_group_id' => $volume->volumeGroup->id
                ]
            );
            $this->fail(new \Exception("Volume '" . $volume->id . "' in failed sync state"));
            return;
        }

        $volume->port = $port;

        $volume->save();

        Log::info('Shared volume ' . $volume->id . ' was assigned port ' . $port . ' on volume group ' . $volume->volumeGroup->id);
    }
}
