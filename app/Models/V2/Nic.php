<?php

namespace App\Models\V2;

use App\Events\V2\Nic\Created;
use App\Events\V2\Nic\Creating;
use App\Events\V2\Nic\Deleted;
use App\Events\V2\Nic\Deleting;
use App\Events\V2\Nic\Saved;
use App\Events\V2\Nic\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
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

class Nic extends Model implements Filterable, Sortable, ResellerScopeable, AvailabilityZoneable, Natable, RouterScopable
{
    use CustomKey, SoftDeletes, Syncable, Taskable, DeletionRules;

    public $keyPrefix = 'nic';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'mac_address',
        'instance_id',
        'network_id',
        'ip_address'
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
        'deleted' => Deleted::class
    ];

    public function getResellerId(): int
    {
        return $this->instance->getResellerId();
    }

    public function getIPAddress(): string
    {
        return $this->ip_address;
    }

    public function getRouter() {
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
            $factory->create('mac_address', Filter::$stringDefaults),
            $factory->create('instance_id', Filter::$stringDefaults),
            $factory->create('network_id', Filter::$stringDefaults),
            $factory->create('ip_address', Filter::$stringDefaults),
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
            $factory->create('mac_address'),
            $factory->create('instance_id'),
            $factory->create('ip_address'),
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
            'mac_address' => 'mac_address',
            'instance_id' => 'instance_id',
            'network_id' => 'network_id',
            'ip_address' => 'ip_address',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
