<?php

namespace App\Models\V2;

use App\Events\V2\Volume\Created;
use App\Events\V2\Volume\Creating;
use App\Events\V2\Volume\Deleted;
use App\Events\V2\Volume\Deleting;
use App\Events\V2\Volume\Saved;
use App\Events\V2\Volume\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultAvailabilityZone;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Volume extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DefaultAvailabilityZone, Syncable;

    public $keyPrefix = 'vol';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;

    protected $casts = [
        'os_type' => 'boolean',
    ];

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
        'capacity',
        'vmware_uuid',
        'os_type',
        'iops',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
        'deleted' => Deleted::class,
    ];

    protected $attributes = [
        'os_volume' => false,
    ];

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function instances()
    {
        return $this->belongsToMany(Instance::class)->using(InstanceVolume::class);
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
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @return bool
     */
    public function getAttachedAttribute()
    {
        if ($this->instances()->count() > 0) {
            return true;
        }

        return false;
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
            $factory->create('os_type', Filter::$numericDefaults),
            $factory->create('iops', Filter::$numericDefaults),
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
            $factory->create('os_type'),
            $factory->create('iops'),
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
            'os_type' => 'os_type',
            'iops' => 'iops',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
