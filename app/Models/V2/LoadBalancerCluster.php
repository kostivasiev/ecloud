<?php

namespace App\Models\V2;

use App\Events\V2\LoadBalancerCluster\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultAvailabilityZone;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class LoadBalancerCluster
 * @package App\Models\V2
 * @method static findOrFail(string $lbcId)
 * @method static forUser(string $user)
 */
class LoadBalancerCluster extends Model implements Filterable, Sortable
{
    use SoftDeletes, CustomKey, DefaultName, DefaultAvailabilityZone;

    public $keyPrefix = 'lbc';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;
    public $table = 'lbcs';

    protected $fillable = [
        'id',
        'name',
        'availability_zone_id',
        'vpc_id',
        'nodes'
    ];

    protected $casts = [
        'nodes' => 'integer',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }
        return $query;
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
            $factory->create('nodes', Filter::$numericDefaults),
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
            $factory->create('nodes'),
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
            'nodes' => 'nodes',
            'config_id' => 'config_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
