<?php

namespace App\Listeners\V2\Instance;

use App\Events\V2\Instance\Creating;
use App\Models\V2\Instance;
use Illuminate\Support\Facades\Log;

class DefaultPlatform
{
    public function handle(Creating $event)
    {
        /** @var Instance $model */
        $model = $event->model;

        if (!empty($model->platform)) {
            Log::info('Platform already set to "' . $model->platform . '" on instance ' . $model->id);
            return;
        }

        if (!$model->applianceVersion) {
            Log::error('Failed to find appliance version for instance ' . $model->id);
            return;
        }

        try {
            $model->platform = $model->applianceVersion->serverLicense()->category;
        } catch (\Exception $exception) {
            Log::error('Failed to determine default platform from appliance version', [$exception]);
            throw $exception;
        }

        Log::info('Default platform on instance ' . $model->id . ' set to ' . $model->platform);

        Log::info(get_class($this) . ' : Finished', ['event' => $event]);
    }
}
