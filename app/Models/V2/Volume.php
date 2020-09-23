<?php

namespace App\Models\V2;

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
 * Class Volume
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 */
class Volume extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DefaultAvailabilityZone;

    public $keyPrefix = 'vol';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
        'capacity',
        'vmware_uuid',
    ];

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
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('capacity', Filter::$stringDefaults),
            $factory->create('vmware_uuid', Filter::$stringDefaults),
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
            $factory->create('vpc_id'),
            $factory->create('availability_zone_id'),
            $factory->create('capacity'),
            $factory->create('vmware_uuid'),
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

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpc_id' => 'vpc_id',
            'availability_zone_id' => 'availability_zone_id',
            'capacity' => 'capacity',
            'vmware_uuid' => 'vmware_uuid',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}