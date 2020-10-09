<?php

namespace App\Jobs\Instance\Deploy;

use App\Jobs\ConfigureNic;
use App\Jobs\Job;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class ConfigureNics extends Job
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {
        Log::info('Performing ConfigureNics for instance ' . $this->data['instance_id']);

        Instance::findOrFail($this->data['instance_id'])->nics()
            ->whereNotNull('network_id')
            ->where('network_id', '!=', '')
            ->each(function ($nic) {
                dispatch((new ConfigureNic($nic)));
            });
    }
}
