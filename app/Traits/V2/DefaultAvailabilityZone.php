<?php

namespace App\Traits\V2;

use App\Models\V2\Vpc;
use Illuminate\Support\Facades\Log;

trait DefaultAvailabilityZone
{
    /**
     * @throws \Exception
     */
    public static function initializeDefaultAvailabilityZone()
    {
        static::creating(function ($instance) {
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
            } else {
                Log::error('Failed to find default Availability Zone for instance ' . $instance->id);
            }
        }
    }
}
