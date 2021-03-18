<?php
namespace App\Listeners\V2\HostGroup;

use App\Jobs\HostGroup\UnusedBilling;
use App\Models\V2\BillingMetric;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use App\Support\Resource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateUnusedBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model instanceof HostGroup) {
            $model = $event->model;
        }

        if ($event->model instanceof Sync) {
            if (!$event->model->completed) {
                return;
            }

            if (Resource::classFromId($event->model->resource_id) != HostGroup::class) {
                return;
            }

            $model = HostGroup::find($event->model->resource_id);
        }

        if (empty($model)) {
            return;
        }

        dispatch(new UnusedBilling($model));

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}
