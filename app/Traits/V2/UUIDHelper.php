<?php

namespace App\Traits\V2;

/**
 * Trait UUIDHelper
 *
 * UUIDHelper related Model functionality
 *
 */
trait UUIDHelper
{

    /** @var int Key length to generate */
    public static $keyLength = 4;

    /**
     * Boot the Model.
     * Create and save UUID on saving a new record
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            $instance->{$instance->getKeyName()} = static::generateId($instance);
        });
    }

    /**
     * Generate a unique id
     * @param $instance
     * @return string
     * @throws \Exception
     */
    public static function generateId($instance)
    {
        $uniqueId = $instance::KEY_PREFIX . '-' .
            bin2hex(
                random_bytes(static::$keyLength)
            );
        if (!$instance->find($uniqueId)) {
            return $uniqueId;
        }
        return static::generateId($instance);
    }
}
