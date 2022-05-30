<?php

namespace App\Models\V2;

use App\Events\V2\Network\Creating;
use App\Events\V2\Network\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use IPLib\Range\Subnet;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Network extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable, Manageable, VpcAble
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'net';

    public $children = [
        'nics',
        'ipAddresses',
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'router_id',
            'subnet'
        ]);

        $this->dispatchesEvents = [
            'creating' => Creating::class,
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->router->getResellerId();
    }

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function vpc()
    {
        return $this->router->vpc();
    }

    public function nics()
    {
        return $this->hasMany(Nic::class);
    }

    public function networkPolicy()
    {
        return $this->hasOne(NetworkPolicy::class);
    }

    public function availabilityZone()
    {
        return $this->router->availabilityZone();
    }

    public function ipAddresses()
    {
        return $this->hasMany(IpAddress::class);
    }

    public function isManaged() :bool
    {
        return (bool) $this->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
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

        $query->whereHas('router', function ($query) {
            $query->where('is_management', false);
        });

        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    protected function getIpAddressLockKey()
    {
        return "ip_address." . $this->id;
    }

    public function withIpAddressLock($callback)
    {
        $lock = Cache::lock($this->getIpAddressLockKey(), 60);
        try {
            $lock->block(60);

            return $callback($this);
        } finally {
            $lock->release();
        }
    }

    public function allocateIpAddress(array $denyList = [], string $type = IpAddress::TYPE_DHCP) : IpAddress
    {
        $ipAddress = app()->make(IpAddress::class);
        $ipAddress->fill([
            'network_id' => $this->id,
            'type' => $type
        ]);
        $ipAddress->allocateAddressAndSave($denyList);

        return $ipAddress;
    }

    public function getNextAvailableIp(array $denyList = [])
    {
        $subnet = Subnet::fromString($this->subnet);
        $ip = $subnet->getStartAddress();

        $start = true;
        while ($start || $ip = $ip->getNextAddress()) {
            $start = false;
            if ($ip == null || !$subnet->contains($ip)) {
                break;
            }

            $checkIp = $ip->toString();
            if ($this->isReservedAddress($checkIp)) {
                Log::debug('IP address "' . $checkIp . '" is reserved, skipping');
                continue;
            }

            if (collect($denyList)->contains($checkIp)) {
                Log::warning('IP address "' . $checkIp . '" is within the deny list, skipping');
                continue;
            }

            if ($this->ipAddresses()->where('ip_address', $checkIp)->count() > 0) {
                Log::debug('IP address "' . $checkIp . '" on network ' . $this->id .' in use');
                continue;
            }

            return $checkIp;
        }

        throw new \Exception('Insufficient available IP\'s in subnet on network ' . $this->id);
    }

    public function getSubnet()
    {
        return Subnet::fromString($this->subnet);
    }

    /**
     * @return \IPLib\Address\AddressInterface
     */
    public function getNetworkAddress()
    {
        return $this->getSubnet()->getStartAddress();
    }

    /**
     * @return \IPLib\Address\AddressInterface
     */
    public function getGatewayAddress()
    {
        return $this->getNetworkAddress()->getNextAddress();
    }

    /**
     * @return \IPLib\Address\AddressInterface
     */
    public function getDhcpServerAddress()
    {
        return $this->getGatewayAddress()->getNextAddress();
    }

    /**
     * @return \IPLib\Address\AddressInterface
     */
    public function getBroadcastAddress()
    {
        return $this->getSubnet()->getEndAddress();
    }

    /**
     * Get subnet prefix, eg 10.0.0.1/24 returns 24
     * @return int
     */
    public function getNetworkPrefix()
    {
        return $this->getSubnet()->getNetworkPrefix();
    }

    public function isReservedAddress($ipAddress)
    {
        return in_array($ipAddress, [
            $this->getBroadcastAddress()->toString(),
            $this->getNetworkAddress()->toString(),
            $this->getGatewayAddress()->toString(),
            $this->getDhcpServerAddress()->toString(),
            // We currently reserve the 3rd usable address for future use
            $this->getDhcpServerAddress()->getNextAddress()->toString(),
        ]);
    }

    public function loadBalancers(): HasManyThrough
    {
        return $this->hasManyThrough(
            LoadBalancer::class,
            LoadBalancerNetwork::class,
            'network_id',
            'id',
            'id',
            'load_balancer_id',
        );
    }


    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'router_id' => $filter->string(),
            'vpc_id' => $filter->for('router.vpc_id')->string(),
            'subnet' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
