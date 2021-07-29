<?php

namespace App\Traits\V2;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

trait CustomKey
{
    /**
     * @throws \Exception
     */
    public static function bootCustomKey()
    {
        static::creating(function ($model) {
            static::addCustomKey($model);
        });
    }

    /**
     * @param $model
     * @throws \Exception
     */
    public static function addCustomKey($model)
    {
        Log::info('Setting Custom Key for ' . get_class($model));

        if (empty($model->keyPrefix)) {
            throw new \Exception('Invalid key prefix');
        }

        $suffix = App::environment() === 'production' ? '' : '-dev';
        if (!empty($model->id)) {
            Log::info('ID already set to "' . $model->id . '" for ' . get_class($model));
            return;
        }

        try {
            do {
                $model->id = $model->keyPrefix . '-' . bin2hex(random_bytes(4)) . $suffix;
            } while ($model->find($model->id));
        } catch (\Exception $exception) {
            Log::error('Failed to set Custom Key on ' . get_class($model), [
                $exception,
            ]);
            throw $exception;
        }

        Log::info('Set Custom Key to "' . $model->id . '" for ' . get_class($model));
    }
}
