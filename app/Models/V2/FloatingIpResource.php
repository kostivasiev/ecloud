<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Syncable pivot for assigning floating IP's to a resource
 */
class FloatingIpResource extends Model
{
    use HasFactory, CustomKey, SoftDeletes, Syncable, Taskable;

    public $keyPrefix = 'fipr';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->table = 'floating_ip_resource';

        $this->fillable([
            'id',
            'floating_ip_id',
            'resource_id',
            'resource_type',
        ]);

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function floatingIp()
    {
        return $this->belongsTo(FloatingIp::class);
    }

    public function resource()
    {
        return $this->morphTo();
    }
}
