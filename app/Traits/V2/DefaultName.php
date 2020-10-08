<?php

namespace App\Traits\V2;

use App\Models\V2\Instance;

trait DefaultName
{
    /**
     * @throws \Exception
     */
    public static function initializeDefaultName()
    {
        static::creating(function ($instance) {
            static::setDefaultName($instance);
        });
    }

    /**
     * @param $instance
     * @throws \Exception
     */
    public static function setDefaultName($instance)
    {
        if (empty($instance->name)) {
            $instance->name = $instance->id;
        }
    }
}
