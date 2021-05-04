<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\JobModel;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class UpdateNetworkAdapter extends Job
{
    use Batchable, JobModel;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/327
     */
    public function handle()
    {
        if (empty($this->model->image->vm_template_name)) {
            Log::info('Skipped UpdateNetworkAdapter for instance ' . $this->model->id . ': no vm template found');
            return;
        }

        foreach ($this->model->nics as $nic) {
            $this->model->availabilityZone->kingpinService()->put(
                '/api/v2/vpc/' . $this->model->vpc->id . '/instance/' . $this->model->id . '/nic/' . $nic->mac_address . '/connect',
                [
                    'json' => [
                        'networkId' => $nic->network_id,
                    ],
                ]
            );
        }
    }
}
