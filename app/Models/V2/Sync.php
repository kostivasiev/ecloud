<?php

namespace App\Models\V2;

use App\Events\V2\Sync\Created;
use App\Events\V2\Sync\Updated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sync extends Model
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'sync';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $dateFormat = 'Y-m-d H:i:s.u';

    const STATUS_INPROGRESS = 'in-progress';
    const STATUS_FAILED     = 'failed';
    const STATUS_COMPLETE   = 'complete';

    const TYPE_UPDATE = 'update';
    const TYPE_DELETE = 'delete';

    protected $fillable = [
        'id',
        'type',
        'completed',
        'failure_reason',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'updated' => Updated::class,
    ];

    public function resource()
    {
        return $this->morphTo();
    }

    public function getStatusAttribute()
    {
        if ($this->failure_reason !== null) {
            return Sync::STATUS_FAILED;
        }
        if ($this->completed) {
            return Sync::STATUS_COMPLETE;
        }
        return Sync::STATUS_INPROGRESS;
    }
}
