<?php

namespace App\Traits\V2;

use Illuminate\Support\Facades\Log;

trait DefaultName
{
    /**
     * @throws \Exception
     */
    public static function bootDefaultName()
    {
        static::creating(function ($model) {
            static::setDefaultName($model);
        });
    }

    /**
     * @param $model
     * @throws \Exception
     */
    public static function setDefaultName($model)
    {
        Log::info('Setting Default Name on ' . $model->id . ' (' . get_class($model) . ')');

        if (empty($model->name)) {
            $model->name = $model->id;
        }

        Log::info('Set Default Name on "' . $model->id . '" (' . get_class($model) . ')');
    }
}
