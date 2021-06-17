<?php
namespace App\Jobs\Instance;

use App\Jobs\Job;
use App\Models\V2\Instance;
use App\Traits\V2\LoggableModelJob;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\Log;

class EndPublicBilling extends Job
{
    use Batchable, LoggableModelJob;

    private $model;

    public function __construct(Instance $instance)
    {
        $this->model = $instance;
    }

    public function handle()
    {
        $this->model->billingMetrics()->each(function ($billingMetric) {
            if (empty($billingMetric->end) && ($billingMetric->category == 'Compute' || $billingMetric->key == 'license.windows')) {
                $billingMetric->setEndDate();
                Log::debug('Ending billing of `' . $billingMetric->key . '` for Instance ' . $this->model->id);
            }
        });
    }
}