<?php

namespace App\Models\V2;

use App\Events\V2\Router\Creating;
use App\Events\V2\Router\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Router
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Router extends Model implements Filterable, Sortable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'rtr';

    public $children = [
        'vpns',
        'networks'
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'availability_zone_id',
            'router_throughput_id',
            'is_management',
            'is_hidden',
        ]);

        $this->casts = [
            'is_management' => 'boolean',
            'is_hidden' => 'boolean',
        ];

        $this->attributes = [
            'is_management' => false,
            'is_hidden' => false,
        ];

        $this->dispatchesEvents = [
            'creating' => Creating::class,
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpns()
    {
        return $this->hasMany(VpnService::class);
    }

    public function firewallPolicies()
    {
        return $this->hasMany(FirewallPolicy::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function routerThroughput()
    {
        return $this->belongsTo(RouterThroughput::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        $query->where('is_hidden', false);
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        $columns = [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('router_throughput_id', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
            $factory->create('is_management', Filter::$enumDefaults),
        ];

        if (Auth::user()->isAdmin()) {
            $columns[] = $factory->create('is_hidden', Filter::$numericDefaults);
        }

        return $columns;
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        $columns = [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('router_throughput_id'),
            $factory->create('vpc_id'),
            $factory->create('availability_zone_id'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
            $factory->create('is_management'),
        ];

        if (Auth::user()->isAdmin()) {
            $columns[] = $factory->create('is_hidden');
        }

        return $columns;
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

    public function databaseNames()
    {
        $columns = [
            'id' => 'id',
            'name' => 'name',
            'router_throughput_id' => 'router_throughput_id',
            'vpc_id' => 'vpc_id',
            'availability_zone_id' => 'availability_zone_id',
            'is_management' => 'is_management',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        if (Auth::user()->isAdmin()) {
            $columns['is_hidden'] = 'is_hidden';
        }

        return $columns;
    }
}
