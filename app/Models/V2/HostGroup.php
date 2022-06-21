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

    public function getAvailableCapacity(): ?array
    {
        try {
            $response = $this->availabilityZone->kingpinService()->get(
                sprintf(KingpinService::GET_CAPACITY_URI, $this->vpc->id, $this->id)
            );
        } catch (\Exception $e) {
            Log::error('Unable to retrieve hostgroup capacity', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
        $response = json_decode($response->getBody()->getContents());

        return [
            'cpu' => [
                'used' => $response->cpuUsedMHz,
                'capacity' => $response->cpuCapacityMHz,
                'percentage' => (int) ceil(($response->cpuUsedMHz /$response->cpuCapacityMHz) * 100),
            ],
            'ram' => [
                'used' => $response->ramUsedMB,
                'capacity' => $response->ramCapacityMB,
                'percentage' => (int) ceil(($response->ramUsedMB /$response->ramCapacityMB) * 100),
            ],
        ];
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
}
