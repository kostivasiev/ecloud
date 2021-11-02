<?php

namespace App\Models\V2;

use App\Events\V2\LoadBalancer\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
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
 * Class LoadBalancer
 * @package App\Models\V2
 * @method static findOrFail(string $loadBalancerId)
 * @method static forUser(string $user)
 */
class LoadBalancer extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, HasFactory;

    public $keyPrefix = 'lb';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'availability_zone_id',
            'vpc_id',
            'load_balancer_spec_id'
        ]);

        $this->dispatchesEvents = [
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function loadBalancerSpec()
    {
        return $this->belongsTo(LoadBalancerSpecification::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function vips()
    {
        return $this->hasMany(Vip::class);
    }

    /**
     * @param $query
     * @param Consumer $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function getNodesAttribute(): int
    {
        return (int) $this->instances()->count();
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
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('load_balancer_spec_id', Filter::$stringDefaults),
            $factory->create('config_id', Filter::$numericDefaults),
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
            $factory->create('availability_zone_id'),
            $factory->create('vpc_id'),
            $factory->create('load_balancer_spec_id'),
            $factory->create('config_id'),
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
            $factory->create('created_at', 'desc'),
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
            'availability_zone_id' => 'availability_zone_id',
            'vpc_id' => 'vpc_id',
            'load_balancer_spec_id' => 'load_balancer_spec_id',
            'config_id' => 'config_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
