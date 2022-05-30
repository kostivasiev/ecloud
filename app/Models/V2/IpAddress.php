<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class IpAddress extends Model implements Searchable, Natable, ResellerScopeable, RouterScopable
{
    use CustomKey, SoftDeletes, DefaultName, HasFactory, DeletionRules, Syncable;

    public $keyPrefix = 'ip';
    public $children;

    const TYPE_DHCP = 'dhcp';
    const TYPE_CLUSTER = 'cluster';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->children = [
            'nics',
            'vip',
        ];

        $this->fillable([
            'id',
            'name',
            'ip_address',
            'network_id',
            'type',
        ]);

        $this->attributes = [
            'type' => self::TYPE_CLUSTER
        ];

        parent::__construct($attributes);
    }

    /**
     * Pivot table ip_address_nic
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function nics()
    {
        return $this->belongsToMany(Nic::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function vip()
    {
        return $this->hasOne(Vip::class);
    }

    public function getIPAddress(): ?string
    {
        return $this->ip_address;
    }

    public function getRouter()
    {
        return $this->network->router;
    }

    public function floatingIp()
    {
        return $this->morphOne(FloatingIp::class, 'resource');
    }

    public function getResellerId(): int
    {
        return $this->network->getResellerId();
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
        return $query->whereHas('network.router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function scopeWithType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'ip_address' => $filter->string(),
            'network_id' => $filter->string(),
            'type' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    public function scopeSortByIp($query)
    {
        if (request()->has('sort')) {
            if (!preg_match('/\:/i', request()->get('sort'))) {
                request()->request->set('sort', request()->get('sort') . ':asc');
            }
            list($field, $direction) = explode(':', request()->get('sort'));
            if ($field == 'ip_address' && in_array(strtolower($direction), ['asc', 'desc'])) {
                $query->orderByRaw('INET_ATON(ip_address) ' . $direction);
            }
        }
    }

    public function setAddressAndSave($ip)
    {
        return $this->network->withIpAddressLock(function () use ($ip) {
            if ($this->network->ipAddresses()->where('ip_address', $ip)->count() > 0) {
                throw new Exception('IP address already assigned');
            }

            $this->ip_address = $ip;
            return $this->save();
        });
    }

    public function allocateAddressAndSave(array $denyList = [])
    {
        return $this->network->withIpAddressLock(function ($network) use ($denyList) {
            $ip = $network->getNextAvailableIp($denyList);
            $this->ip_address = $ip;
            return $this->save();
        });
    }
}
