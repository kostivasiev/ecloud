<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Jobs\TaskJob;
use App\Models\V2\FloatingIp;
use App\Models\V2\Instance;
use App\Models\V2\Nat;
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
        $destination_id = null;

        if ((!empty($this->data['floating_ip_id']) || $this->data['requires_floating_ip']) && $instance->nics()->count() < 1) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $instance->id . ': Failed. Instance has no NIC'));
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {
            $destination_id = $this->data['floating_ip_id'];
        }

        if ($this->data['requires_floating_ip']) {
            $floatingIp = app()->make(FloatingIp::class);
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
            $destination_id = $floatingIp->id;
        }

        if (!empty($destination_id)) {
            $nic = $instance->nics()->first();
            $nat = app()->make(Nat::class);
            $nat->destination_id = $destination_id;
            $nat->destinationable_type = 'fip';
            $nat->translated_id = $nic->id;
            $nat->translatedable_type = 'nic';
            $nat->save();
            Log::info('Floating IP (' . $destination_id . ') assigned to NIC (' . $nic->id . ')');
        }

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
