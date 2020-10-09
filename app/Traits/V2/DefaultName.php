<?php

namespace App\Traits\V2;

trait DefaultName
{
    /**
     * @throws \Exception
     */
    public static function bootDefaultName()
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
