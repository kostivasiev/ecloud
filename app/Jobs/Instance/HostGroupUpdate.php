<?php

namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class HostGroupUpdate extends Job
{
    use Batchable, LoggableModelJob;

    private $model;
    private $host_group_id;

    public function __construct(Instance $instance, $data)
    {
        $this->model = $instance;
        $this->host_group_id = (isset($data['host_group_id'])) ? $data['host_group_id'] : null;
    }

    public function handle()
    {
        if (($this->model->host_group_id == $this->host_group_id) || (empty($this->host_group_id))) {
            Log::info(get_class($this) . ' : Finished: No changes required', ['id' => $this->model->id]);
            return;
        }

        $originalHostGroup = HostGroup::findOrFail($this->host_group_id);
        $newHostGroup = HostGroup::findOrFail($this->model->host_group_id);
        $cyclePower = ($originalHostGroup->hostSpec->id != $newHostGroup->hostSpec->id);

        if ($cyclePower) {
            try {// Power off
                $this->model->availabilityZone->kingpinService()
                    ->delete('/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/power');
            } catch (RequestException $exception) {
                if ($exception->getCode() !== 404) {
                    $this->fail($exception);
                }
                $message = 'Unable to power off ' . $this->model->id . ' on Vpc ' . $this->model->vpc_id;
                Log::warning(get_class($this) . ' : ' . $message);
                return;
            }
        }

        try {
            $this->model->availabilityZone->kingpinService()->post(
                '/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/reschedule',
                [
                    'json' => [
                        'hostGroupId' => $this->model->host_group_id,
                    ],
                ]
            );
        } catch (RequestException $exception) {
            if ($exception->getCode() !== 404) {
                $this->fail($exception);
            }
            $message = 'Unable to move ' . $this->model->id . ' to host group ' . $this->model->host_group_id;
            Log::warning(get_class($this) . ' : ' . $message);
        }

        if ($cyclePower) {
            try {// Power on
                $this->model->availabilityZone->kingpinService()
                    ->post('/api/v2/vpc/' . $this->model->vpc_id . '/instance/' . $this->model->id . '/power');
            } catch (RequestException $exception) {
                if ($exception->getCode() !== 404) {
                    $this->fail($exception);
                }
                $message = 'Unable to power on ' . $this->model->id . ' on Vpc' . $this->model->vpc_id . ', skipping.';
                Log::warning(get_class($this) . ' : ' . $message);
            }
        }
    }
}
