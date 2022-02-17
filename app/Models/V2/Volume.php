<?php

namespace App\Models\V2;

use App\Events\V2\Volume\Creating;
use App\Events\V2\Volume\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Volume extends Model implements Filterable, Sortable, ResellerScopeable, AvailabilityZoneable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, Taskable, HasFactory;

    public $keyPrefix = 'vol';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'availability_zone_id',
            'capacity',
            'vmware_uuid',
            'os_volume',
            'iops',
            'is_shared',
            'volume_group_id',
            'port'
        ]);

        $this->casts = [
            'os_volume' => 'boolean',
            'is_shared' => 'boolean',
        ];

        $this->attributes = [
            'os_volume' => false,
            'is_shared' => false,
        ];

        $this->dispatchesEvents = [
            'creating' => Creating::class,
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

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

    public function volumeGroup()
    {
        return $this->belongsTo(VolumeGroup::class);
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
            $query->whereHas('instances', function ($query) {
                $query->where('is_hidden', '=', false);
            })->doesntHave('instances', 'or');
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

    public function getTypeAttribute()
    {
        return $this->attributes['os_volume'] ? 'os' : 'data';
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
            $factory->create('os_volume', Filter::$numericDefaults),
            $factory->boolean()->create('is_shared', '1', '0'),
            $factory->create('volume_group_id', Filter::$stringDefaults),
            $factory->create('port', Filter::$numericDefaults),
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
            $factory->create('os_volume'),
            $factory->create('iops'),
            $factory->create('is_shared'),
            $factory->create('volume_group_id'),
            $factory->create('port'),
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
            'os_volume' => 'os_volume',
            'iops' => 'iops',
            'is_shared' => 'is_shared',
            'volume_group_id' => 'volume_group_id',
            'port' => 'port',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
