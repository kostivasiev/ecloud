<?php

namespace App\Traits\V2;

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
        Log::info('Setting Custom Key on ' . $model->id . ' (' . get_class($model) . ')');

        if (empty($model->keyPrefix)) {
            throw new \Exception('Invalid key prefix');
        }

        try {
            do {
                $model->id = $model->keyPrefix . '-' . bin2hex(random_bytes(4));
            } while ($model->find($model->id));
        } catch (\Exception $exception) {
            Log::error('Failed to set Custom Key on ' . $model->id . ' (' . get_class($model) . ')', [
                $exception,
            ]);
        }

        Log::info('Set Custom Key to "' . $model->id . '" (' . get_class($model) . ')');
    }
}
