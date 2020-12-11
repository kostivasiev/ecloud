<?php

namespace App\Models\V2;

use App\Events\V2\FloatingIp\Created;
use App\Events\V2\FloatingIp\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class FloatingIp
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser($user)
 * @method static withRegion($regionId)
 */
class FloatingIp extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'fip';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'name',
        'vpc_id',
        'deleted'
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'deleted' => Deleted::class
    ];

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($model) {
            $model->attributes['deleted'] = time();
            $model->save();
        });
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    /**
     * DNAT destination
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function nat()
    {
        return $this->morphOne(Nat::class, 'destinationable', null, 'destination_id');
    }

    public function getResourceIdAttribute()
    {
        return ($this->nat) ? $this->nat->translated_id : null;
    }

    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $query->whereHas('vpc', function ($query) use ($user) {
                $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
                if (!empty($resellerId)) {
                    $query->where('reseller_id', '=', $resellerId);
                }
            });
        }
        return $query;
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
