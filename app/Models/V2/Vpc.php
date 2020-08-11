<?php

namespace App\Models\V2;

use App\Traits\V2\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class VirtualPrivateClouds
 * @package App\Models\V2
 * @method static findOrFail(string $vdcUuid)
 */
class Vpc extends Model implements Filterable, Sortable
{
    use SoftDeletes;
    use UUIDHelper {
        boot as UUIDHelperBoot;
    }

    public const KEY_PREFIX = 'vpc';
    protected $connection = 'ecloud';
    protected $table = 'virtual_private_clouds';
    protected $primaryKey = 'id';
    protected $fillable = ['id', 'name', 'reseller_id'];

    public $incrementing = false;
    public $timestamps = true;

    /**
     * If no name is passed when creating, default the name to the id value
     */
    public static function boot()
    {
        static::UUIDHelperBoot();
        static::creating(function ($instance) {
            if (empty($instance->name)) {
                $instance->name = $instance->getKey();
            }
        });
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
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    /**
     * Scope a query to only include resources for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('reseller_id', $resellerId);
        }

        return $query;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function dhcps()
    {
        return $this->belongsTo(Dhcp::class, 'id', 'vpc_id');
    }
}
