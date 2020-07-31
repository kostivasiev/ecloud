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
 * Class Routers
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Router extends Model implements Filterable, Sortable
{
    use UUIDHelper, SoftDeletes;

    public const KEY_PREFIX = 'rtr';
    protected $connection = 'ecloud';
    protected $table = 'routers';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'name', 'vpc_id'];

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
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
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
            $factory->create('vpc_id'),
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
            'vpc_id'       => 'vpc_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * Many to Many with Gateways table
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function gateways()
    {
        return $this->belongsToMany(Gateway::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vpns()
    {
        return $this->hasMany(Vpn::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
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
        if (!$user->isAdministrator || !empty($user->resellerId)) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }

        return $query;
    }
}
