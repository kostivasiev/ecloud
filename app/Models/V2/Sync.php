<?php

namespace App\Models\V2;

use App\Events\V2\Sync\Saved;
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

    protected $fillable = [
        'id',
        'resource_id',
        'completed',
        'failure_reason',
    ];

    protected $casts = [
        'completed' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'saved' => Saved::class
    ];
}
