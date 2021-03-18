<?php
namespace App\Listeners\V2\Host;

use App\Jobs\HostGroup\UnusedBilling;
use App\Models\V2\Host;
use App\Models\V2\HostGroup;
use App\Models\V2\Sync;
use App\Support\Resource;
use Illuminate\Support\Facades\Log;

class UpdateUnusedBilling
{
    public function handle($event)
    {
        Log::info(get_class($this) . ' : Started', ['id' => $event->model->id]);

        if ($event->model instanceof Host) {
            $model = $event->model->hostGroup;
        }

        if ($event->model instanceof Sync) {
            if (!$event->model->completed) {
                return;
            }

            if (Resource::classFromId($event->model->resource_id) != Host::class) {
                return;
            }

            $model = Host::find($event->model->resource_id)->hostGroup;
        }

        if (empty($model)) {
            return;
        }

        dispatch(new UnusedBilling($model));

        Log::info(get_class($this) . ' : Finished', ['id' => $event->model->id]);
    }
}