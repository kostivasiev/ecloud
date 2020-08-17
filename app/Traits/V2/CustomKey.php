<?php

namespace App\Traits\V2;

trait CustomKey
{
    /**
     * @throws \Exception
     */
    public static function initializeCustomKey()
    {
        static::creating(function ($instance) {
            if (empty($instance->keyPrefix)) {
                throw new \Exception('Invalid key prefix');
            }
            do {
                $instance->id = $instance->keyPrefix . '-' . bin2hex(random_bytes(4));
            } while(static::find($instance->id));
        });
    }
}
