<?php

namespace App\Jobs\Instance;

use App\Jobs\TaskJob;
use App\Models\V2\Instance;
use App\Models\V2\Task;
use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

class PowerOn extends TaskJob
{
    private $data;

    public function __construct(Task $task, $data)
    {
        parent::__construct($task);

        $this->data = $data;
    }

    /**
     * @see https://gitlab.devops.ukfast.co.uk/ukfast/api.ukfast/ecloud/-/issues/328
     */
    public function handle()
    {
        Log::info(get_class($this) . ' : Started', ['data' => $this->data]);

        $instance = Instance::findOrFail($this->data['instance_id']);
        $vpc = Vpc::findOrFail($this->data['vpc_id']);
        $instance->availabilityZone->kingpinService()->post(
            '/api/v2/vpc/' . $vpc->id . '/instance/' . $instance->id . '/power'
        );

        Log::info(get_class($this) . ' : Finished', ['data' => $this->data]);
    }
}
