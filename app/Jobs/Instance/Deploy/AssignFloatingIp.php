<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\FloatingIp\Assign;
use App\Jobs\Job;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use Exception;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends Job
{
    use Batchable;

    private $instance;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
    }

    public function handle()
    {
        Log::debug(get_class($this) . ' : Started', ['id' => $this->instance->id]);

        if ((!empty($this->instance->deploy_data['floating_ip_id']) || $this->instance->deploy_data['requires_floating_ip']) && $this->instance->nics()->count() < 1) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $this->instance->id . ': Failed. Instance has no NIC'));
            return;
        }

        if (!empty($this->instance->deploy_data['floating_ip_id'])) {
            $floatingIp = FloatingIp::findOrFail($this->instance->deploy_data['floating_ip_id']);
        }

        if ($this->instance->deploy_data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
        }

        if (!empty($floatingIp)) {
            $nic = $this->instance->nics()->first();

            dispatch(new Assign([
                'floating_ip_id' => $floatingIp->id,
                'resource_id' => $nic->id
            ]));

            Log::info('Floating IP (' . $floatingIp->id . ') assigned to NIC (' . $nic->id . ')');
        }

        Log::debug(get_class($this) . ' : Finished', ['id' => $this->instance->id]);
    }
}
