<?php

namespace App\Models\V2;

use App\Events\V2\RouterCreated;
use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Routers
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 */
class Router extends Model implements Filterable, Sortable
{
    use UUIDHelper, SoftDeletes;

    public const STATUS_OK = 0;
    public const STATUS_FAIL = 1;

    public const KEY_PREFIX = 'rtr';
    protected $connection = 'ecloud';
    protected $table = 'routers';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'name'];
    protected $visible = ['id', 'name', 'created_at', 'updated_at'];

    public $incrementing = false;
    public $timestamps = true;

    protected $dispatchesEvents = [
        'created' => RouterCreated::class,
    ];

    public function gateways()
    {
        return $this->belongsToMany(Gateway::class);
    }

    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    public function vpns()
    {
        return $this->hasOne(Vpn::class, 'id', 'router_id');
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
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults)
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
            $factory->create('created_at'),
            $factory->create('updated_at')
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

    public function databaseNames()
    {
        return [
            'id'         => 'id',
            'name'       => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
