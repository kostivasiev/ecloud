<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Models\V2\Vpc;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class AssignFloatingIp extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Starting AssignFloatingIp for instance ' . $this->data['instance_id']);
        $instance = Instance::findOrFail($this->data['instance_id']);

        if (empty($this->data['floating_ip_id']) && !$this->data['requires_floating_ip']) {
            Log::info('No floating IP required, skipping.');
            return;
        }

        if (!empty($this->data['floating_ip_id'])) {

        }
        //floating_ip_id
        //requires_floating_ip


        if (false) {
            $this->fail(new Exception('AssignFloatingIp failed for ' . $instance->id));
            return;
        }


    }
}
