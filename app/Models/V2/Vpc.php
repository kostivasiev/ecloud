<?php

namespace App\Models\V2;

use App\Events\V2\VpcCreated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class VirtualPrivateClouds
 * @package App\Models\V2
 * @method static findOrFail(string $vdcUuid)
 * @method static forUser(string $user)
 */
class Vpc extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'vpc';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'reseller_id',
        'region_id',
    ];

    protected $dispatchesEvents = [
        'created' => VpcCreated::class
    ];

    /**
     * If no name is passed when creating, default the name to the id value
     */
    public static function boot()
    {
        static::created(function ($instance) {
            if (empty($instance->name)) {
                $instance->name = $instance->getKey();
                $instance->save();
            }
        });
        parent::boot();
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

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
            if (!empty($resellerId)) {
                $query->where('reseller_id', '=', $resellerId);
            }
        }
        return $query;
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\FilterFactory $factory
     * @return array|\UKFast\DB\Ditto\Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('reseller_id', Filter::$stringDefaults),
            $factory->create('region_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults)
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
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
            $factory->create('created_at'),
            $factory->create('updated_at')
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
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
            'id'         => 'id',
            'name'       => 'name',
            'reseller_id' => 'reseller_id',
            'region_id' => 'region_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
