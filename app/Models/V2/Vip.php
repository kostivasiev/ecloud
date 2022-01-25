<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Vip extends Model implements Filterable, Sortable
{
    use CustomKey, DefaultName, SoftDeletes, Syncable, Taskable, HasFactory;

    public $keyPrefix = 'vip';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'name',
        'load_balancer_id',
        'network_id',
        'ip_address_id'
    ];

    public function loadBalancer()
    {
        return $this->belongsTo(LoadBalancer::class);
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function ipAddress()
    {
        return $this->belongsTo(IpAddress::class);
    }

//    public function getFloatingIpIdAttribute()
//    {
//        if ($this->ipAddress()->exists() && $this->ipAddress->floatingIp->exists()) {
//            return $this->ipAddress->floatingIp->id;
//        }
//
//        return null;
//    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query
            ->whereHas('network.router.vpc', function ($query) use ($user) {
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

        $lock = Cache::lock("ip_address." . $this->id, 60);
        try {
            $lock->block(60);

            $ip = $this->network->getNextAvailableIp();

            $ipAddress = app()->make(IpAddress::class);
            $ipAddress->fill([
                'ip_address' => $ip,
                'network_id' => $this->network->id,
                'type' => IpAddress::TYPE_CLUSTER
            ]);
            $ipAddress->save();

            $this->ipAddress()->associate($ipAddress)->save();

            Log::info(IpAddress::TYPE_CLUSTER . ' IP address ' . $ipAddress->id . ' (' . $ipAddress->ip_address . ') was assigned to VIP ' . $this->id);

            return $ipAddress;
        } finally {
            $lock->release();
        }
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
            $factory->create('load_balancer_id', Filter::$stringDefaults),
            $factory->create('network_id', Filter::$stringDefaults),
            $factory->create('ip_address_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('load_balancer_id', Filter::$stringDefaults),
            $factory->create('network_id', Filter::$stringDefaults),
            $factory->create('ip_address_id', Filter::$stringDefaults),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('created_at', 'asc'),
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
            'load_balancer_id' => 'load_balancer_id',
            'network_id' => 'network_id',
            'ip_address_id' => 'ip_address_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at'
        ];
    }
}
