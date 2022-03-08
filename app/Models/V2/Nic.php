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
use UKFast\DB\Ditto\Exceptions\InvalidSortException;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sort;
use UKFast\DB\Ditto\Sortable;

class Nic extends Model implements Filterable, Sortable, ResellerScopeable, AvailabilityZoneable, Natable, RouterScopable
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
     * IF the database has the ip_address field populated return that, otherwise look for
     * an IP address in the ip_addresses table of type 'normal' (DHCP)
     * @return mixed|null
     */
    public function getIpAddressAttribute()
    {
        if (!empty($this->attributes['ip_address'])) {
            return $this->attributes['ip_address'];
        }

        if ($this->ipAddresses()->withType(IpAddress::TYPE_NORMAL)->exists()) {
            return $this->ipAddresses()->withType(IpAddress::TYPE_NORMAL)->first()->ip_address;
        }

        return null;
    }

    /**
     * @param array $denyList
     * @param string $type
     * @return mixed|void
     * @throws \Exception
     */
    public function assignIpAddress(array $denyList = [], string $type = IpAddress::TYPE_NORMAL) : IpAddress
    {
        $lock = Cache::lock("ip_address." . $this->network->id, 60);
        try {
            $lock->block(60);

            $ip = $this->network->getNextAvailableIp($denyList);

            $ipAddress = app()->make(IpAddress::class);
            $ipAddress->fill([
                'ip_address' => $ip,
                'network_id' => $this->network->id,
                'type' => $type
            ]);

            $this->ipAddresses()->save($ipAddress);
            Log::info('IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') was assigned to NIC ' . $this->id . ', type: ' . $type);

            return $ipAddress;
        } finally {
            $lock->release();
        }
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

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('mac_address', Filter::$stringDefaults),
            $factory->create('instance_id', Filter::$stringDefaults),
            $factory->create('network_id', Filter::$stringDefaults),
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
            $factory->create('mac_address'),
            $factory->create('instance_id'),
            $factory->create('network_id'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|Sort|Sort[]|null
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
            'mac_address' => 'mac_address',
            'instance_id' => 'instance_id',
            'network_id' => 'network_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
