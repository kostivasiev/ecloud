<?php

namespace App\Listeners\V2\Volume;

use App\Events\V2\Volume\Creating;
use Illuminate\Support\Facades\Log;

class UKFastId
{
    public function handle(Creating $event)
    {
        $model = $event->model;

        Log::info('Setting UKFast ID on ' . $model->id . ' (' . get_class($model) . ')');

        try {
            do {
                $model->id = $model->keyPrefix . '-' . bin2hex(random_bytes(4));
            } while ($model->find($model->id));
        } catch (\Exception $exception) {
            Log::error('Failed to set UKFast ID on ' . $model->id . ' (' . get_class($model) . ')', [
                $exception,
            ]);
        }

        Log::info('Set UKFast ID to "' . $model->id . '" (' . get_class($model) . ')');
    }
}
