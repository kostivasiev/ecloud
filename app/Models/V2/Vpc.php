<?php

namespace App\Models\V2;

use App\Events\V2\Vpc\Deleting;
use App\Events\V2\Vpc\Saved;
use App\Events\V2\Vpc\Saving;
use App\Jobs\Vpc\UpdateSupportEnabledBilling;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Vpc extends Model implements Searchable, ResellerScopeable, RegionAble
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpc';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'reseller_id',
        'region_id',
        'console_enabled',
        'advanced_networking',
    ];

    protected $dispatchesEvents = [
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
    ];

    public $children = [
        'routers',
        'instances',
        'loadBalancers',
        'volumes',
        'floatingIps',
        'hostGroups',
    ];

    protected $casts = [
        'console_enabled' => 'bool',
        'advanced_networking' => 'bool',
    ];

    public function getResellerId(): int
    {
        return $this->reseller_id;
    }

    public function dhcps()
    {
        return $this->hasMany(Dhcp::class);
    }

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function volumes()
    {
        return $this->hasMany(Volume::class);
    }

    public function floatingIps()
    {
        return $this->hasMany(FloatingIp::class);
    }

    public function loadBalancers()
    {
        return $this->hasMany(LoadBalancer::class);
    }

    public function hostGroups()
    {
        return $this->hasMany(HostGroup::class);
    }

    public function billingMetrics()
    {
        return $this->hasMany(BillingMetric::class);
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
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    /**
     * Get the vpc's support flag.
     *
     * @return string
     */
    public function getSupportEnabledAttribute()
    {
        return (bool) BillingMetric::getActiveByKey($this, UpdateSupportEnabledBilling::getKeyName());
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'reseller_id' => $filter->string(),
            'region_id' => $filter->string(),
            'console_enabled' => $filter->boolean(),
            'support_enabled' => $filter->boolean(),
            'advanced_networking' => $filter->boolean(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
        $sieve->setDefaultSort('created_at', 'desc');
    }
}
