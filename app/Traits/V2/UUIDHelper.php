<?php
/**
 * V1Refactor - Brought in from V1 namespace
 */

namespace App\Traits\V2;

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
    public static function bootUUIDHelper()
    {
        static::creating(function ($instance) {
            $instance->{$instance->getKeyName()} = Uuid::uuid4()->toString();
        });
    }
}
