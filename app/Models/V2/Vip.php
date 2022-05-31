<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Vip extends Model implements Searchable, ResellerScopeable
{
    use CustomKey, DefaultName, SoftDeletes, Syncable, Taskable, HasFactory;

    public $keyPrefix = 'vip';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'load_balancer_network_id',
            'ip_address_id',
            'config_id',
        ]);

        $this->casts = [
            'config_id' => 'integer',
        ];

        parent::__construct($attributes);
    }

    public function loadBalancerNetwork(): BelongsTo
    {
        return $this->belongsTo(LoadBalancerNetwork::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class);
    }

    public function getResellerId(): int
    {
        return $this->loadBalancerNetwork->loadBalancer->getResellerId();
    }

    public function getLoadBalancerIdAttribute()
    {
        return $this->loadBalancerNetwork->loadBalancer->id;
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query
            ->whereHas('loadBalancerNetwork.network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            });
    }

    /**
     * @param array $denyList
     * @param string $type
     * @return mixed|void
     * @throws \Exception
     */
    public function assignClusterIp() : IpAddress
    {
        if ($this->ipAddress()->exists()) {
            throw new \Exception('Cluster IP address already assigned to VIP');
        }

        $network = $this->loadBalancerNetwork->network;
        $ipAddress = $network->allocateIpAddress([], IpAddress::TYPE_CLUSTER);

        $this->ipAddress()->associate($ipAddress)->save();
        Log::info(IpAddress::TYPE_CLUSTER . ' IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') was assigned to VIP ' . $this->id);

        return $ipAddress;
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'load_balancer_id' => $filter->for('loadBalancerNetwork.load_balancer_id')->string(),
            'ip_address_id' => $filter->string(),
            'config_id' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
