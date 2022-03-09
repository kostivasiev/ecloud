<?php

namespace App\Models\V2;

use App\Events\V2\AvailabilityZoneCapacity\Saved;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class AvailabilityZoneCapacity
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 */
class AvailabilityZoneCapacity extends Model implements Searchable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes;

    public $keyPrefix = 'azc';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'availability_zone_id',
        'type',
        'current',
        'alert_warning',
        'alert_critical',
        'max',
    ];

    protected $casts = [
        'current' => 'float',
        'alert_warning' => 'integer',
        'alert_critical' => 'integer',
        'max' => 'integer'
    ];

    protected $dispatchesEvents = [
        'saved' => Saved::class,
    ];

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'type' => $filter->string(),
            'current' => $filter->numeric(),
            'alert_warning' => $filter->numeric(),
            'alert_critical' => $filter->numeric(),
            'max' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
