<?php

namespace App\Datastore;

use ReflectionClass;

class Status
{
    const QUEUED = 'Queued';

    const BUILDING = 'Building';

    const COMPLETED = 'Completed';

    const EXPANDING = 'Expanding';

    const FAILED = 'Failed';

    const DELETED = 'Deleted';

    /**
     * Return class constants
     * @return array
     * @throws \ReflectionException
     */
    public static function all()
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}
