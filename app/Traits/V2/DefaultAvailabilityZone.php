<?php

namespace App\Traits\V2;

use App\Models\V2\Vpc;

trait DefaultAvailabilityZone
{
    /**
     * @throws \Exception
     */
    public static function initializeDefaultAvailabilityZone()
    {
        static::created(function ($instance) {
            static::setDefaultAvailabilityZone($instance);
        });
    }

    public static function setDefaultAvailabilityZone($instance)
    {
        if (empty($instance->availability_zone_id)) {
            $availabilityZone = Vpc::forUser(app('request')->user)
                ->findOrFail($instance->vpc_id)
                ->region
                ->availabilityZones
                ->first();
            if ($availabilityZone) {
                $instance->availability_zone_id = $availabilityZone->getKey();
                $instance->save();
            }
        }
    }
}
