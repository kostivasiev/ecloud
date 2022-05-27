<?php

namespace App\Models\V2;

use App\Events\V2\Nic\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Nic extends Model implements Searchable, ResellerScopeable, AvailabilityZoneable, Natable, RouterScopable
{
    use HasFactory, CustomKey, SoftDeletes, Syncable, Taskable, DeletionRules, DefaultName;

    public $keyPrefix = 'nic';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'mac_address',
            'instance_id',
            'network_id',
        ]);

        $this->dispatchesEvents = [
            'deleted' => Deleted::class
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->instance->getResellerId();
    }

    public function getIPAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getRouter()
    {
        return $this->network->router;
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function sourceNat()
    {
        return $this->morphOne(Nat::class, 'sourceable', null, 'source_id');
    }

    public function destinationNat()
    {
        return $this->morphOne(Nat::class, 'translatedable', null, 'translated_id');
    }

    public function floatingIp()
    {
        return $this->morphOne(FloatingIp::class, 'resource');
    }

    public function availabilityZone()
    {
        return $this->network->router->availabilityZone();
    }

    /**
     * Pivot table ip_address_nic
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ipAddresses()
    {
        return $this->belongsToMany(IpAddress::class);
    }

    /**
     * Look for an IP address in the ip_addresses table of type 'dhcp' (DHCP)
     * @return mixed|null
     */
    public function getIpAddressAttribute()
    {
        if ($this->ipAddresses()->withType(IpAddress::TYPE_DHCP)->exists()) {
            return $this->ipAddresses()->withType(IpAddress::TYPE_DHCP)->first()->ip_address;
        }

        return null;
    }

    /**
     * @param array $denyList
     * @param string $type
     * @return mixed|void
     * @throws \Exception
     */
    public function assignIpAddress(array $denyList = [], string $type = IpAddress::TYPE_DHCP) : IpAddress
    {
        $ipAddress = $this->network->allocateIpAddress($denyList, $type);

        $this->ipAddresses()->save($ipAddress);
        Log::info('IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') was assigned to NIC ' . $this->id . ', type: ' . $type);

        return $ipAddress;
    }

    /**
     * Override method from DeletionRules trait.
     * @return bool
     */
    public function canDelete()
    {
        return $this->floatingIp()->exists() == false;
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
        return $query->whereHas('network.router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'mac_address' => $filter->string(),
            'instance_id' => $filter->string(),
            'network_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
