<?php

namespace App\Traits\V2;

use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

trait DefaultAvailabilityZone
{
    /**
     * @throws \Exception
     */
    public static function bootDefaultAvailabilityZone()
    {
        static::creating(function ($model) {
            static::setDefaultAvailabilityZone($model);
        });
    }

    /**
     * @param $model
     * @throws \Exception
     */
    public static function setDefaultAvailabilityZone($model)
    {
        Log::info('Setting Default Availability Zone on ' . $model->id . ' (' . get_class($model) . ')');

        if (!empty($model->availability_zone_id)) {
            Log::info('Availability Zone ID already set to "' . $model->availability_zone_id . '" for ' . get_class($model));
            return;
        }

        $availabilityZone = Vpc::forUser(Auth::user())
            ->findOrFail($model->vpc_id)
            ->region
            ->availabilityZones
            ->orderBy('created_at')
            ->first();
        if ($availabilityZone) {
            $model->availability_zone_id = $availabilityZone->id;
        } else {
            Log::error('Failed to find default Availability Zone for instance ' . $model->id);
            throw new \Exception('Failed to find default Availability Zone for instance ' . $model->id);
        }

        Log::info('Set Default Availability Zone to "' . $model->id . '" (' . get_class($model) . ')');
    }
}
