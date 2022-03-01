<?php

namespace App\Models\V2;

use App\Events\V2\AvailabilityZoneCapacity\Saved;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class AvailabilityZoneCapacity
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 */
class AvailabilityZoneCapacity extends Model implements Filterable, Sortable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes;

    public $keyPrefix = 'azc';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'availability_zone_id',
        'type',
        'current',
        'alert_warning',
        'alert_critical',
        'max',
    ];

    protected $casts = [
        'current' => 'float',
        'alert_warning' => 'integer',
        'alert_critical' => 'integer',
        'max' => 'integer'
    ];

    protected $dispatchesEvents = [
        'saved' => Saved::class,
    ];

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
            $factory->create('current', Filter::$numericDefaults),
            $factory->create('alert_warning', Filter::$numericDefaults),
            $factory->create('alert_critical', Filter::$numericDefaults),
            $factory->create('max', Filter::$numericDefaults),
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
            $factory->create('availability_zone_id'),
            $factory->create('type'),
            $factory->create('current'),
            $factory->create('alert_warning'),
            $factory->create('alert_critical'),
            $factory->create('max'),
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
            $factory->create('created_at', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'availability_zone_id' => 'name',
            'type' => 'vpc_id',
            'current' => 'availability_zone_id',
            'alert_warning' => 'capacity',
            'alert_critical' => 'vmware_uuid',
            'max' => 'created_at',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
