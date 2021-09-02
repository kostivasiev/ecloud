<?php

namespace App\Models\V2;

use App\Events\V2\Vpn\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class Vpns
 * @package App\Models\V2
 * @method static findOrFail(string $dhcpId)
 * @method static forUser(string $user)
 */
class VpnService extends Model implements Filterable, Sortable, AvailabilityZoneable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpn';

    public $children = [
        'vpnSessions',
        'vpnEndpoints',
    ];

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'router_id',
            'name',
            'nsx_uuid',
        ];
        parent::__construct($attributes);
    }

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function vpnEndpoints()
    {
        return $this->hasMany(VpnEndpoint::class);
    }

    public function vpnSessions()
    {
        return $this->hasMany(VpnSession::class);
    }

    public function availabilityZone()
    {
        return $this->router->availabilityZone();
    }

    public function getResellerId(): int
    {
        return $this->router->getResellerId();
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
        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('router_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('nsx_uuid', Filter::$stringDefaults),
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
            $factory->create('router_id'),
            $factory->create('name'),
            $factory->create('nsx_uuid'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'router_id' => 'router_id',
            'name' => 'name',
            'nsx_uuid' => 'nsx_uuid',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
