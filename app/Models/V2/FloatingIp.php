<?php

namespace App\Models\V2;

use App\Events\V2\FloatingIp\Deleted;
use App\Exceptions\V2\FloatingIp\AssignException;
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

class FloatingIp extends Model implements Filterable, Sortable, ResellerScopeable
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
        'deleted' => Deleted::class
    ];

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
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

    public function resource()
    {
        return $this->morphTo();
    }

    public function assign($resource)
    {
        if (!empty($this->resource_id)) {
            throw new AssignException();
        }

        $this->withTaskLock(function ($model) use ($resource) {
            $this->resource()->associate($resource);

            if ($resource instanceof Nic) {
                if (!$this->destinationNat()->exists()) {
                    $nat = app()->make(Nat::class);
                    $nat->destination()->associate($this);
                    $nat->translated()->associate($resource);
                    $nat->action = Nat::ACTION_DNAT;
                    $nat->save();
                }

                if (!$this->sourceNat()->exists()) {
                    $nat = app()->make(Nat::class);
                    $nat->source()->associate($resource);
                    $nat->translated()->associate($this);
                    $nat->action = NAT::ACTION_SNAT;
                    $nat->save();
                }
            }

            $this->save();
        });
    }

    public function unassign()
    {
        $this->withTaskLock(function ($model) {
            if ($this->resource instanceof Nic) {
                if ($this->sourceNat()->exists()) {
                    $this->sourceNat->delete();
                }
                if ($this->destinationNat()->exists()) {
                    $this->destinationNat->delete();
                }
            }

            $this->resource()->dissociate();

            $this->save();
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
            $factory->create('resource_id', Filter::$stringDefaults),
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
            $factory->create('resource_id'),
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
            'resource_id' => 'resource_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
