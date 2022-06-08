<?php

namespace App\Models\V2;

use App\Events\V2\Instance\Creating;
use App\Events\V2\Instance\Deleted;
use App\Services\V2\KingpinService;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Instance extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable, Manageable, VpcAble
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

    public $keyPrefix = 'i';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'image_id',
        'vcpu_cores',
        'ram_capacity',
        'availability_zone_id',
        'locked',
        'is_hidden',
        'backup_enabled',
        'deployed',
        'deploy_data',
        'host_group_id',
        'volume_group_id',
    ];

    protected $appends = [
        'volume_capacity',
    ];

    protected $casts = [
        'locked' => 'boolean',
        'backup_enabled' => 'boolean',
        'deployed' => 'boolean',
        'deploy_data' => 'array',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'deleted' => Deleted::class,
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->timestamps = true;
    }

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function loadBalancer()
    {
        return $this->belongsTo(LoadBalancer::class);
    }

    public function loadBalancerNode()
    {
        return $this->hasOne(LoadBalancerNode::class);
    }

    public function nics()
    {
        return $this->hasMany(Nic::class);
    }

    public function getVolumeCapacityAttribute()
    {
        $sum = 0;
        foreach ($this->volumes()->get() as $volume) {
            $sum += $volume->capacity;
        }
        return $sum;
    }

    public function getPlatformAttribute()
    {
        return $this?->image?->platform;
    }

    public function volumes()
    {
        return $this->belongsToMany(Volume::class)->using(InstanceVolume::class);
    }

    public function volumeGroup()
    {
        return $this->belongsTo(VolumeGroup::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }

        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('is_hidden', false)->where('reseller_id', $user->resellerId());
        });
    }

    public function getGuestAdminCredentials(): ?Credential
    {
        return $this->credentials()
            ->where('username', config('instance.guest_admin_username.' . strtolower($this->platform)))
            ->first();
    }

    public function getGuestSupportCredentials(): ?Credential
    {
        return $this->credentials()
            ->where('username', config('instance.guest_support_username.' . strtolower($this->platform)))
            ->first();
    }

    public function image()
    {
        return $this->belongsTo(Image::class);
    }

    public function hostGroup()
    {
        return $this->belongsTo(HostGroup::class);
    }

    public function billingMetrics()
    {
        return $this->hasMany(BillingMetric::class, 'resource_id', 'id');
    }

    public function isManaged() :bool
    {
        return $this->loadBalancer()->exists();
    }

    public function isHidden(): bool
    {
        return $this->isManaged() || $this->is_hidden;
    }

    public function affinityRuleMember()
    {
        return $this->hasOne(AffinityRuleMember::class, 'instance_id');
    }

    public function getHostGroupId(): ?string
    {
        if (!empty($this->attributes['host_group_id'])) {
            return $this->attributes['host_group_id'];
        }

        try {
            $response = $this->availabilityZone
                ->kingpinService()
                ->get(
                    sprintf(KingpinService::GET_HOSTGROUP_URI, $this->vpc->id, $this->id)
                );
        } catch (Exception $e) {
            $message = 'Shared hostgroup id could not be found for instance ' . $this->id;
            Log::info($message);
            return null;
        }
        return (json_decode($response->getBody()->getContents()))->hostGroupID;
    }

    /**
     * @param string $hostGroupId
     * @param string $affinityRuleId
     * @return bool
     * @throws Exception
     */
    public function hasAffinityRule(string $hostGroupId, string $affinityRuleId): bool
    {
        try {
            $response = $this->availabilityZone->kingpinService()
                ->get(
                    sprintf(KingpinService::GET_CONSTRAINT_URI, $hostGroupId)
                );
        } catch (Exception $e) {
            $message = 'Failed to retrieve ' . $hostGroupId . ' : ' . $e->getMessage();
            Log::info($message);
            throw new Exception($message);
        }
        return collect(json_decode($response->getBody()->getContents(), true))
                ->where('ruleName', '=', $affinityRuleId)
                ->count() > 0;
    }

    /**
     * Loads software using the InstanceSoftware model as a pivot
     * @return HasManyThrough
     */
    public function software(): HasManyThrough
    {
        return $this->hasManyThrough(
            Software::class,
            InstanceSoftware::class,
            'instance_id',
            'id',
            'id',
            'software_id'
        );
    }

    /**
     * Configures a sieve instance so that query builders
     * can be modified
     *
     * @return void
     */
    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'vpc_id' => $filter->string(),
            'image_id' => $filter->string(),
            'vcpu_cores' => $filter->string(),
            'ram_capacity' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'locked' => $filter->boolean(),
            'is_hidden' => $filter->boolean(),
            'platform' => $filter->for('image.platform')->string(),
            'backup_enabled' => $filter->string(),
            'host_group_id' => $filter->string(),
            'volume_group_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
