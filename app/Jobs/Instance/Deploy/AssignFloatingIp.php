<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\FloatingIp\Assign;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
use App\Traits\V2\LoggableModelJob;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends Job
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
        if ((!empty($this->model->deploy_data['floating_ip_id']) || $this->model->deploy_data['requires_floating_ip']) && $this->model->nics()->count() < 1) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $this->model->id . ': Failed. Instance has no NIC'));
            return;
        }

        if (!empty($this->model->deploy_data['floating_ip_id'])) {
            $floatingIp = FloatingIp::findOrFail($this->model->deploy_data['floating_ip_id']);
        } else if ($this->model->deploy_data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->model->vpc->id;
            $floatingIp->save();
        }

        if (!empty($floatingIp)) {
            $nic = $this->model->nics()->first();

            $nat = app()->make(Nat::class);
            $nat->destination()->associate($floatingIp);
            $nat->translated()->associate($nic);
            $nat->action = Nat::ACTION_DNAT;
            $nat->save();

            $nat = app()->make(Nat::class);
            $nat->source()->associate($nic);
            $nat->translated()->associate($floatingIp);
            $nat->action = NAT::ACTION_SNAT;
            $nat->save();

            Log::info('Floating IP (' . $floatingIp->id . ') assigned to NIC (' . $nic->id . ')');
        }
    }
}
