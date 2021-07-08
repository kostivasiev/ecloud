<?php

namespace App\Models\V2;

use App\Events\V2\Vpc\Deleting;
use App\Events\V2\Vpc\Saved;
use App\Events\V2\Vpc\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Vpc extends Model implements Filterable, Sortable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpc';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'reseller_id',
        'region_id',
        'console_enabled',
        'advanced_networking',
    ];

    protected $dispatchesEvents = [
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
    ];

    public $children = [
        'routers',
        'instances',
        'loadBalancerClusters',
        'volumes',
        'floatingIps',
    ];

    protected $casts = [
        'console_enabled' => 'bool',
        'advanced_networking' => 'bool',
    ];

    public function getResellerId(): int
    {
        return $this->reseller_id;
    }

    public function dhcps()
    {
        return $this->hasMany(Dhcp::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function volumes()
    {
        return $this->hasMany(Volume::class);
    }

    public function floatingIps()
    {
        return $this->hasMany(FloatingIp::class);
    }

    public function loadBalancerClusters()
    {
        return $this->hasMany(LoadBalancerCluster::class);
    }

    public function vpcSupports()
    {
        return $this->hasMany(VpcSupport::class);
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
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    /**
     * @return bool
     */
    public function getSupportEnabledAttribute(): bool
    {
        return $this->vpcSupports->filter(function ($vpcSupport) {
            return $vpcSupport->active;
        })->count() > 0;
    }

    public function enableSupport($date = null)
    {
        if ($this->supportEnabled) {
            return true;
        }

        $vpcSupport = app()->make(VpcSupport::class);
        $vpcSupport->start_date = $date ?? Carbon::now(new \DateTimeZone(config('app.timezone')));
        $vpcSupport->vpc()->associate($this);
        return $vpcSupport->save();
    }

    public function disableSupport()
    {
        if (!$this->supportEnabled) {
            return true;
        }

        $vpcSupport = $this->vpcSupports->filter(function($vpcSupport) {
            return $vpcSupport->active;
        })->first();
        $vpcSupport->end_date = Carbon::now(new \DateTimeZone(config('app.timezone')));
        return $vpcSupport->save();
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
            $factory->create('reseller_id', Filter::$stringDefaults),
            $factory->create('region_id', Filter::$stringDefaults),
            $factory->create('console_enabled', Filter::$enumDefaults),
            $factory->create('advanced_networking', Filter::$enumDefaults),
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
            $factory->create('reseller_id'),
            $factory->create('region_id'),
            $factory->create('console_enabled'),
            $factory->create('advanced_networking'),
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
            'reseller_id' => 'reseller_id',
            'region_id' => 'region_id',
            'console_enabled' => 'console_enabled',
            'advanced_networking' => 'advanced_networking',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
