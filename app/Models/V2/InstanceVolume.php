<?php

namespace App\Models\V2;

use App\Events\V2\InstanceVolume\Created;
use App\Events\V2\InstanceVolume\Deleted;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstanceVolume extends Pivot
{
    protected $dispatchesEvents = [
        'deleted' => Deleted::class,
        'created' => Created::class,
    ];
}
