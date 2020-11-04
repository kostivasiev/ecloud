<?php


namespace App\Jobs\Instance;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use Illuminate\Support\Facades\Log;

class UpdateTaskJob extends TaskJob
{
    private $instance;
    private $data;

    public function __construct(Task $task, Instance $instance, array $data)
    {
        parent::__construct($task);

        $this->instance = $instance;
        $this->data = $data;
    }

    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        if (!isset($this->data['ram_capacity']) && !isset($this->data['vcpu_cores'])) {
            Log::info('No compute changes required');
            return;
        }

        $parameters = [];
        $reboot = false;
        $ram_limit = (($this->instance->platform == 'Windows') ? 16 : 3) * 1024;

        if (isset($this->data['ram_capacity']) && $this->data['ram_capacity'] > 0) {
            if (($this->instance->ram_capacity > $this->data['ram_capacity']) ||
                ($this->instance->ram_capacity < $ram_limit && $this->data['ram_capacity'] >= $ram_limit)) {
                $reboot = true;
            }
            $parameters['ramMiB'] = $this->data['ram_capacity'];
            $this->instance->ram_capacity = $this->data['ram_capacity'];
        }

        if (isset($this->data['vcpu_cores']) && $this->data['vcpu_cores'] > 0) {
            if ($this->instance->vcpu_cores > $this->data['vcpu_cores']) {
                $reboot = true;
            }
            $parameters['numCPU'] = $this->data['vcpu_cores'];
            $this->instance->vcpu_cores = $this->data['vcpu_cores'];
        }
        $parameters['guestShutdown'] = $reboot;

        $this->instance->availabilityZone->kingpinService()->put(
            '/api/v2/vpc/' . $this->instance->vpc_id . '/instance/' . $this->instance->getKey() . '/resize',
            [
                'json' => $parameters,
            ]
        );
        $this->instance->save();

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
