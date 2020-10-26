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
        Log::info('Starting AssignFloatingIp for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);
        $destination = null;

        if ((!empty($this->data['floating_ip_id']) || $this->data['requires_floating_ip'])
            && $instance->nics()->count() < 1) {
            $this->fail(
                new Exception('AssignFloatingIp failed for ' . $instance->getKey() . ': ' . 'Failed. Instance has no NIC')
            );
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {
            $destination = $this->data['floating_ip_id'];
        }

        if ($this->data['requires_floating_ip']) {
            $floatingIp = new FloatingIp;
            $floatingIp->vpc_id = $this->data['vpc_id'];
            $floatingIp->save();
            $destination = $floatingIp->getKey();
        }

        if (!empty($destination)) {
            $nic = $instance->nics()->first();

            $nat = new Nat;
            $nat->destination = $destination;
            $nat->destinationable_type = 'fip';
            $nat->translated = $nic->getKey();
            $nat->translatedable_type = 'nic';
            $nat->save();

            Log::info('Floating IP (' . $destination . ') assigned to NIC (' . $nic->getKey() . ')');
        }
    }
}
