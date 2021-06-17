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
    private string $hostGroupId;

    public function __construct(Instance $instance, string $hostGroupId)
    {
        $this->model = $instance;
        $this->hostGroupId = $hostGroupId;
    }

    public function handle()
    {
        if ($this->model->hostGroup && $this->model->hostGroup->id == $this->hostGroupId) {
            Log::warning(get_class($this) . ': Instance ' . $this->model->id . ' is already in the host group ' . $this->hostGroupId . ', nothing to do');
            return;
        }
        $this->model->billingMetrics()->each(function ($billingMetric) {
            if (empty($billingMetric->end) && ($billingMetric->category == 'Compute' || $billingMetric->key == 'license.windows')) {
                $billingMetric->setEndDate();
                Log::debug('Ending billing of `' . $billingMetric->key . '` for Instance ' . $this->model->id);
            }
        });
    }
}
