<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class RouterThroughput
 * @package App\Models\V2
 */
class RouterThroughput extends Model implements Searchable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules;

    public string $keyPrefix = 'rtp';

    public $children = [
        'routers',
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->casts = [
            'committed_bandwidth' => 'integer',
        ];

        $this->fillable([
            'id',
            'availability_zone_id',
            'name',
            'committed_bandwidth',
        ]);

        parent::__construct($attributes);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    /**
     * @param Builder $query
     * @param Consumer $user
     * @return Builder
     */
    public function scopeForUser(Builder $query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->whereHas('availabilityZone.region', function ($query) {
            $query->where('is_public', '=', true);
        })->whereHas('availabilityZone', function ($query) {
            $query->where('is_public', '=', true);
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'ip_address' => $filter->string(),
            'network_id' => $filter->string(),
            'type' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
            'id' => $filter->string(),
            'name' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'committed_bandwidth' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
