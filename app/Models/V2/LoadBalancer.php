<?php

namespace App\Models\V2;

use App\Events\V2\LoadBalancer\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class LoadBalancer
 * @package App\Models\V2
 * @method static findOrFail(string $loadBalancerId)
 * @method static forUser(string $user)
 */
class LoadBalancer extends Model implements Searchable, AvailabilityZoneable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, HasFactory;

    public $keyPrefix = 'lb';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'availability_zone_id',
            'vpc_id',
            'load_balancer_spec_id'
        ]);

        $this->dispatchesEvents = [
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function loadBalancerSpec()
    {
        return $this->belongsTo(LoadBalancerSpecification::class);
    }

    public function loadBalancerNodes(): HasMany
    {
        return $this->hasMany(LoadBalancerNode::class);
    }

    public function instances()
    {
        return $this->hasManyThrough(
            Instance::class,
            LoadBalancerNode::class,
            'load_balancer_id',
            'id',
            'id',
            'instance_id'
        );
    }

    public function getResellerId(): int
    {
        return $this->vpc->reseller_id;
    }

    public function getVipsTotal(): int
    {
        $total = 0;
        $this->loadBalancerNetworks->each(function ($loadBalancerNetwork) use (&$total) {
            $total += $loadBalancerNetwork->vips->count();
        });
        return $total;
    }

    /**
     * Loads networks using the LoadBalancerNetworks pivot
     * @return HasManyThrough
     */
    public function networks(): HasManyThrough
    {
        return $this->hasManyThrough(
            Network::class,
            LoadBalancerNetwork::class,
            'load_balancer_id',
            'id',
            'id',
            'network_id'
        );
    }

    public function loadBalancerNetworks()
    {
        return $this->hasMany(LoadBalancerNetwork::class);
    }

    public function getNetworkIdAttribute()
    {
        return $this->networks()->exists() ?  $this->networks()->first()->id : null;
    }

    /**
     * @param $query
     * @param Consumer $user
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

    public function getNodesAttribute(): int
    {
        return (int) $this->loadBalancerNodes()->count();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'vpc_id' => $filter->string(),
            'load_balancer_spec_id' => $filter->string(),
            'config_id' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
