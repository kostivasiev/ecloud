<?php

namespace App\Models\V2;

use App\Events\V2\Region\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
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
 * Class Region
 * @package App\Models\V2
 * @method static findOrFail(string $vdcUuid)
 * @method static forUser(string $user)
 */
class Region extends Model implements Filterable, Sortable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules;

    public $keyPrefix = 'reg';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    public $children = [
        'availabilityZones',
        'vpcs',
    ];

    public function availabilityZones()
    {
        return $this->hasMany(AvailabilityZone::class);
    }

    public function vpcs()
    {
        return $this->hasMany(Vpc::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->where('is_public', '=', 1);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->boolean()->create('is_public', '1', '0'),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('is_public'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('name', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'is_public' => 'is_public',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
