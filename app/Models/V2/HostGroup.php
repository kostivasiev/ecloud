<?php

namespace App\Models\V2;

use App\Events\V2\HostGroup\Deleted;
use App\Events\V2\HostGroup\Deleting;
use App\Events\V2\HostGroup\Saved;
use App\Events\V2\HostGroup\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultAvailabilityZone;
use App\Traits\V2\DefaultName;
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
 * Class HostGroup
 * @package App\Models\V2
 */
class HostGroup extends Model implements Filterable, Sortable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, Taskable, DefaultAvailabilityZone;

    public string $keyPrefix = 'hg';

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
            'host_spec_id',
            'windows_enabled',
        ]);

        $this->casts = [
            'windows_enabled' => 'boolean'
        ];

        $this->dispatchesEvents = [
            'saving' => Saving::class,
            'saved' => Saved::class,
            'deleting' => Deleting::class,
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

    public function hostSpec()
    {
        return $this->belongsTo(HostSpec::class);
    }

    public function hosts()
    {
        return $this->hasMany(Host::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
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
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory): array
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('host_spec_id', Filter::$stringDefaults),
            $factory->create('windows_enabled', Filter::$enumDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory): array
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('vpc_id'),
            $factory->create('availability_zone_id'),
            $factory->create('host_spec_id'),
            $factory->create('windows_enabled'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory): array
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    public function databaseNames(): array
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpc_id' => 'vpc_id',
            'availability_zone_id' => 'availability_zone_id',
            'host_spec_id' => 'host_spec_id',
            'windows_enabled' => 'windows_enabled',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }

    public function getAvailableResources()
    {
        // Ram calculations
        $ramCapacity = ($this->hosts->count() * $this->hostSpec->ram_capacity) - ($this->hosts->count() * 2);
        $instanceRam = $this->instances->sum('ram_capacity') / 1024;
        $totalAvailable = $ramCapacity - $instanceRam;

        // CPU calculations
        $physicalCores = $this->hostSpec->cpu_cores;
        $vcpuCapacity = $physicalCores * 8;
        $vcpuUsed = $this->instances->sum('vcpu_cores');
        $vcpuAvailable = $vcpuCapacity - $vcpuUsed;

        return [
            'hosts' => $this->hosts->count(),
            'ram' => [
                'capacity_gb' => $ramCapacity,
                'used_gb' => $instanceRam,
                'available_gb' => $totalAvailable,
            ],
            'vcpu' => [
                'capacity' => $vcpuCapacity,
                'used' => $vcpuUsed,
                'available' => $vcpuAvailable,
            ]
        ];
    }
}
