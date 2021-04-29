<?php

namespace App\Models\V2;

use App\Events\V2\Task\Created;
use App\Events\V2\Task\Updated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $dateFormat = 'Y-m-d H:i:s.u';

    const STATUS_INPROGRESS = 'in-progress';
    const STATUS_FAILED     = 'failed';
    const STATUS_COMPLETE   = 'complete';

    protected $fillable = [
        'id',
        'completed',
        'failure_reason',
        'name',
        'job',
        'data',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'data'      => 'array',
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
            return static::STATUS_FAILED;
        }
        if ($this->completed) {
            return static::STATUS_COMPLETE;
        }
        return static::STATUS_INPROGRESS;
    }
}
