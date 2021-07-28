<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class CreateFloatingIp extends Job
{
    use Batchable, LoggableModelJob;

    public $tries = 1;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        if ($this->model->deploy_data['requires_floating_ip'] !== true) {
            Log::info(get_class($this) . ' : Floating IP creation is not required, skipping');
            return;
        }

        if ($this->model->nics()->count() < 1) {
            $this->fail(new Exception('CreateFloatingIp failed for ' . $this->model->id . ': Failed. Instance has no NIC'));
            return;
        }

        $floatingIp = app()->make(FloatingIp::class);
        $floatingIp->vpc_id = $this->model->vpc->id;
        $floatingIp->availability_zone_id = $this->model->availability_zone_id;
        $floatingIp->syncSave();
        // Not very nice this, but we need some way of passing the fip id to the AwaitFloatingIpSync job.
        $deploy_data = $this->model->deploy_data;
        $deploy_data['floating_ip_id'] = $floatingIp->id;
        $this->model->deploy_data = $deploy_data;
        $this->model->save();

        Log::info('New Floating IP (' . $floatingIp->id . ') was created');
    }
}
