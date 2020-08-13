<?php

namespace App\Models\V2;

use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
class Vpn extends Model implements Filterable, Sortable
{
    use UUIDHelper, SoftDeletes;

    public const KEY_PREFIX = 'vpn';
    protected $connection = 'ecloud';
    protected $table = 'vpns';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'router_id', 'availability_zone_id'];
    protected $visible = ['id', 'router_id', 'availability_zone_id', 'created_at', 'updated_at'];

    public $incrementing = false;
    public $timestamps = true;

    /**
     * @param \UKFast\DB\Ditto\Factories\FilterFactory $factory
     * @return array|\UKFast\DB\Ditto\Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('router_id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
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
            $factory->create('router_id'),
            $factory->create('availability_zone_id'),
            $factory->create('created_at'),
            $factory->create('updated_at')
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
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
            'id'                   => 'id',
            'router_id'            => 'router_id',
            'availability_zone_id' => 'availability_zone_id',
            'created_at'           => 'created_at',
            'updated_at'           => 'updated_at',
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('router.vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }

        return $query;
    }
}
