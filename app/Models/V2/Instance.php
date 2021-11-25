<?php

namespace App\Models\V2;

use App\Events\V2\Instance\Creating;
use App\Events\V2\Instance\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Exceptions\InvalidSortException;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sort;
use UKFast\DB\Ditto\Sortable;

class Instance extends Model implements Filterable, Sortable, ResellerScopeable, AvailabilityZoneable, Manageable, VpcAble
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

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
        'platform',
        'backup_enabled',
        'deployed',
        'deploy_data',
        'host_group_id',
        'volume_group_id',
        'load_balancer_id'
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
            $factory->create('image_id', Filter::$stringDefaults),
            $factory->create('vcpu_cores', Filter::$stringDefaults),
            $factory->create('ram_capacity', Filter::$stringDefaults),
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->boolean()->create('locked', '1', '0'),
            $factory->boolean()->create('is_hidden', '1', '0'),
            $factory->create('platform', Filter::$stringDefaults),
            $factory->create('backup_enabled', Filter::$stringDefaults),
            $factory->create('host_group_id', Filter::$stringDefaults),
            $factory->create('volume_group_id', Filter::$stringDefaults),
            $factory->create('load_balancer_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|Sort[]
     * @throws InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('vpc_id'),
            $factory->create('image_id'),
            $factory->create('vcpu_cores'),
            $factory->create('ram_capacity'),
            $factory->create('availability_zone_id'),
            $factory->create('locked'),
            $factory->create('platform'),
            $factory->create('backup_enabled'),
            $factory->create('host_group_id'),
            $factory->create('volume_group_id'),
            $factory->create('load_balancer_id'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|Sort|Sort[]|null
     * @throws InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('created_at', 'desc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpc_id' => 'vpc_id',
            'image_id' => 'image_id',
            'vcpu_cores' => 'vcpu_cores',
            'ram_capacity' => 'ram_capacity',
            'availability_zone_id' => 'availability_zone_id',
            'locked' => 'locked',
            'platform' => 'platform',
            'backup_enabled' => 'backup_enabled',
            'host_group_id' => 'host_group_id',
            'volume_group_id' => 'volume_group_id',
            'load_balancer_id' => 'load_balancer_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
