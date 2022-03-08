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
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class RouterThroughput
 * @package App\Models\V2
 */
class RouterThroughput extends Model implements Filterable, Sortable, AvailabilityZoneable
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

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('committed_bandwidth', Filter::$numericDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('availability_zone_id'),
            $factory->create('name'),
            $factory->create('committed_bandwidth'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'availability_zone_id' => 'availability_zone_id',
            'name' => 'name',
            'committed_bandwidth' => 'committed_bandwidth',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
