<?php

namespace App\Traits\V1;

use Ramsey\Uuid\Uuid;

/**
 * Trait UUIDHelper
 *
 * UUIDHelper related Model functionality
 *
 */
trait UUIDHelper
{
    /**
     * Boot the Model.
     * Create and save UUID on saving a new record
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($instance) {
            $instance->{$instance->getKeyName()} = Uuid::uuid4()->toString();
        });
    }

    // The better way to do it, just not enabled yet...
//    public static function initializeUUIDHelper()
//    {
//        static::creating(function ($instance) {
//            $instance->{$instance->getKeyName()} = Uuid::uuid4()->toString();
//        });
//    }
}
