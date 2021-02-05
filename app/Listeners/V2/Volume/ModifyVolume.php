<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Saved;
use App\Jobs\Volume\CapacityIncrease;
use App\Jobs\Volume\IopsChange;
use App\Jobs\Volume\MarkSyncCompleted;
use App\Models\V2\Volume;
use Illuminate\Support\Facades\Log;

class ModifyVolume
{
    public function handle(Saved $event)
    {
        Log::info(get_class($this) . ' : Started', ['model' => $event->model]);

        dispatch((new CapacityIncrease($event))->chain([
            new IopsChange($event),
            new MarkSyncCompleted($event),
        ]));

        Log::info(get_class($this) . ' : Finished', ['model' => $event->model]);
    }
}
