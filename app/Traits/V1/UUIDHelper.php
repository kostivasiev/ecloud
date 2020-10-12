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

        // DO NOT DO THIS - DO NOT DO THIS - DO NOT DO THIS - DO NOT DO THIS
        static::creating(function ($instance) {
            $instance->{$instance->getKeyName()} = Uuid::uuid4()->toString();
        });
        // DO NOT DO THIS - DO NOT DO THIS - DO NOT DO THIS - DO NOT DO THIS
    }
}
