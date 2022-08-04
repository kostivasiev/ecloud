<?php

namespace App\Models\V2;

use App\Events\V2\HostGroup\Deleted;
use App\Services\V2\KingpinService;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class HostGroup
 * @package App\Models\V2
 */
class HostGroup extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

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

        $this->appends = [
            'ram_capacity',
            'ram_used',
            'ram_available',
            'vcpu_capacity',
            'vcpu_used',
            'vcpu_available',
        ];

        $this->dispatchesEvents = [
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

    public function isPrivate(): bool
    {
        return !is_null($this->vpc_id);
    }

    public function resourceTierHostGroups()
    {
        return $this->hasMany(ResourceTierHostGroup::class);
    }

    public function resourceTiers(): HasManyThrough
    {
        return $this->hasManyThrough(
            ResourceTier::class,
            ResourceTierHostGroup::class,
            'host_group_id',
            'id',
            'id',
            'resource_tier_id'
        );
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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'host_spec_id' => $filter->string(),
            'windows_enabled' => $filter->boolean(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    public function getCapacity(): ?array
    {
        try {
            if ($this->isPrivate()) {
                $response = $this->availabilityZone->kingpinService()->get(
                    sprintf(KingpinService::PRIVATE_HOST_GROUP_CAPACITY, $this->vpc->id, $this->id)
                );

                $response = json_decode($response->getBody()->getContents());
            } else {
                $response = $this->availabilityZone->kingpinService()->post(
                    KingpinService::SHARED_HOST_GROUP_CAPACITY,
                    [
                        'json' => [
                            'hostGroupIds' => [
                                static::mapId($this->availabilityZone->id, $this->id)
                            ],
                        ],
                    ]
                );
                $response = json_decode($response->getBody()->getContents())[0];
            }
        } catch (\Exception $e) {
            Log::error('Unable to retrieve hostgroup capacity', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }

        return static::formatHostGroupCapacity($this->availabilityZone->id, $response);
    }

    public static function formatHostGroupCapacity(string $availabilityZoneId, \StdClass $rawHostGroupCapacity): array
    {
        return [
            'id' => static::reverseMapId($availabilityZoneId, $rawHostGroupCapacity->hostGroupId),
            'cpu' => [
                'capacity' => $rawHostGroupCapacity->cpuCapacityMHz,
                'used' => $rawHostGroupCapacity->cpuUsedMHz,
                'percentage' => $rawHostGroupCapacity->cpuUsage,
            ],
            'ram' => [
                'capacity' => $rawHostGroupCapacity->ramCapacityMB,
                'used' => $rawHostGroupCapacity->ramUsedMB,
                'percentage' => $rawHostGroupCapacity->ramUsage,
            ],
        ];
    }

    /**
     * Check if the host group has sufficient compute resources
     * @param int $ram
     * @return bool
     */
    public function canProvision(int $ram): bool
    {
        $capacityThresholdPercent = config('hostgroup.capacity.threshold');

        $capacity = $this->getCapacity();

        $message = 'Checking host group ' . $this->id . ' capacity';

        if ($capacity['ram']['capacity'] == 0 || $capacity['cpu']['capacity'] == 0) {
            Log::info($message . ': The host group has 0 compute capacity. It may not contain an active host.');
            return false;
        }

        $projectedRamUse = $capacity['ram']['used'] + $ram;

        $projectedRamUsePercent = (int) ceil(($projectedRamUse / $capacity['ram']['capacity']) * 100);

        if ($capacity['cpu']['percentage'] > $capacityThresholdPercent ||
            $projectedRamUsePercent > $capacityThresholdPercent) {
            Log::info($message . ': The host group has insufficient compute capacity.', [
                'projectedRamUsePercent' => $projectedRamUsePercent,
                'currentCpuUsePercent' => $capacity['cpu']['percentage']
            ]);
            return false;
        }

        return true;
    }

    public function getRamCapacityAttribute()
    {
        return ($this->hosts->count() * $this->hostSpec->ram_capacity) - ($this->hosts->count() * 2);
    }

    public function getRamUsedAttribute()
    {
        return $this->instances->sum('ram_capacity') / 1024;
    }

    public function getRamAvailableAttribute()
    {
        return ($this->ram_capacity - $this->ram_used) - $this->ram_reserved;
    }

    public function getRamReservedAttribute()
    {
        return $this->hosts->count() * 2;
    }

    public function getVcpuCapacityAttribute()
    {
        return ($this->hostSpec->cpu_cores * 8) * $this->hosts->count();
    }

    public function getVcpuUsedAttribute()
    {
        return $this->instances->sum('vcpu_cores');
    }

    public function getVcpuAvailableAttribute()
    {
        return $this->vcpu_capacity - $this->vcpu_used;
    }

    /**
     * Map proposed High CPU ID to existing cluster name
     * @param string $availabilityZoneId
     * @param string $newHostgroupId
     * @return string|null Existing cluster name
     */
    public static function mapId(string $availabilityZoneId, string $newHostgroupId): ?string
    {
        return config('host-group-map')[$availabilityZoneId][$newHostgroupId] ?? $newHostgroupId;
    }

    /**
     * Map existing cluster name to a proposed HighCPU id
     * @param string $availabilityZoneId
     * @param string $existingClusterName
     * @return string|null High CPU host group ID
     */
    public static function reverseMapId(string $availabilityZoneId, string $existingClusterName): ?string
    {
        return array_flip(config('host-group-map'))[$availabilityZoneId][$existingClusterName] ?? $existingClusterName;
    }
}
