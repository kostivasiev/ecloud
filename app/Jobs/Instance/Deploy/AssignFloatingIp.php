<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\FloatingIp\Assign;
use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use Exception;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends TaskJob
{
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);

        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);

        if ((!empty($this->data['floating_ip_id']) || $this->data['requires_floating_ip']) && $instance->nics()->count() < 1) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $instance->id . ': Failed. Instance has no NIC'));
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {
            $floatingIp = FloatingIp::findOrFail($this->data['floating_ip_id']);
        }

        if ($this->data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
        }

        if (!empty($floatingIp)) {
            $nic = $instance->nics()->first();

            dispatch(new Assign([
                'floating_ip_id' => $floatingIp->getKey(),
                'resource_id' => $nic->getKey()
            ]));

            Log::info('Floating IP (' . $floatingIp->getKey() . ') assigned to NIC (' . $nic->getKey() . ')');
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
