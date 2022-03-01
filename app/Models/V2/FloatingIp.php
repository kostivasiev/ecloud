<?php

namespace App\Models\V2;

use App\Events\V2\FloatingIp\Deleted;
use App\Events\V2\FloatingIp\Created;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class FloatingIp extends Model implements Filterable, Sortable, ResellerScopeable, AvailabilityZoneable, Natable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

    public $keyPrefix = 'fip';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'availability_zone_id',
        'rdns_hostname',
    ];

    protected $dispatchesEvents = [
        'deleted' => Deleted::class,
    ];

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function getIPAddress(): string
    {
        return $this->ip_address;
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function sourceNat()
    {
        return $this->morphOne(Nat::class, 'translatedable', null, 'translated_id');
    }

    public function destinationNat()
    {
        return $this->morphOne(Nat::class, 'destinationable', null, 'destination_id');
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function resource()
    {
        return $this->morphTo();
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
            $factory->create('availability_zone_id', Filter::$stringDefaults),
            $factory->create('ip_address', Filter::$stringDefaults),
            $factory->create('resource_id', Filter::$stringDefaults),
            $factory->create('rdns_hostname', Filter::$stringDefaults),
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
            $factory->create('vpc_id'),
            $factory->create('availability_zone_id'),
            $factory->create('ip_address'),
            $factory->create('resource_id'),
            $factory->create('rdns_hostname'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
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
            'availability_zone_id' => 'availability_zone_id',
            'ip_address' => 'ip_address',
            'resource_id' => 'resource_id',
            'rdns_hostname' => 'rdns_hostname',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
