<?php

namespace App\Models\V2;

use App\Events\V2\FloatingIp\Deleted;
use App\Events\V2\FloatingIp\Deleting;
use App\Events\V2\FloatingIp\Saved;
use App\Events\V2\FloatingIp\Saving;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class FloatingIp extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, Syncable, Taskable;

    public $keyPrefix = 'fip';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'deleted'
    ];

    protected $dispatchesEvents = [
        'saving' => Saving::class,
        'saved' => Saved::class,
        'deleting' => Deleting::class,
        'deleted' => Deleted::class
    ];

    /**
     * @deprecated Use sourceNat (aka SNAT) or destinationNat (aka DNAT)
     */
    public function getResourceIdAttribute()
    {
        return ($this->destinationNat()->exists()) ? $this->destinationNat->translated_id : null;
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
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

    public function scopeWithRegion($query, $regionId)
    {
        return $query->whereHas('vpc.region', function ($query) use ($regionId) {
            $query->where('id', '=', $regionId);
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
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('ip_address', Filter::$stringDefaults),
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
            $factory->create('ip_address'),
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
            'ip_address' => 'ip_address',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
