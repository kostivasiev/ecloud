<?php

namespace App\Jobs\Instance\Undeploy;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;
use UKFast\Admin\Monitoring\AdminClient;

class RemoveMonitoring extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $instance = $this->model;

        $monitoringAdminClient = app()->make(AdminClient::class)->setResellerId($instance->vpc->reseller_id);

        $devices = $monitoringAdminClient->devices()->getAll([
            'reference_type:eq' => 'server',
            'reference_id:eq' => $instance->id,
        ]);

        if (count($devices) > 0) {
            Log::info('Removing monitoring device ' . $devices[0]->id);
            $monitoringAdminClient->devices()->deleteById($devices[0]->id);
        }
    }
}
