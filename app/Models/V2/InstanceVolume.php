<?php

namespace App\Models\V2;

use App\Events\V2\InstanceVolume\Created;
use App\Events\V2\InstanceVolume\Creating;
use App\Events\V2\InstanceVolume\Deleted;
use App\Events\V2\InstanceVolume\Deleting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InstanceVolume extends Pivot
{
    use HasFactory;

    protected $dispatchesEvents = [
        'deleting' => Deleting::class,
        'creating' => Creating::class,
        'deleted' => Deleted::class,
        'created' => Created::class,
    ];
}
