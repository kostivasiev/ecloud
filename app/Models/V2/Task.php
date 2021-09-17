<?php

namespace App\Models\V2;

use App\Events\V2\Task\Created;
use App\Events\V2\Task\Updated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Task extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'task';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $dateFormat = 'Y-m-d H:i:s.u';

    const STATUS_INPROGRESS = 'in-progress';
    const STATUS_FAILED     = 'failed';
    const STATUS_COMPLETE   = 'complete';

    protected $fillable = [
        'id',
        'reseller_id',
        'completed',
        'failure_reason',
        'name',
        'job',
        'data',
    ];

    protected $casts = [
        'completed' => 'boolean',
        'data'      => 'array',
    ];

    protected $dispatchesEvents = [
        'created' => Created::class,
        'updated' => Updated::class,
    ];


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
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    public function resource()
    {
        return $this->morphTo();
    }

    public function getStatusAttribute()
    {
        if ($this->failure_reason !== null) {
            return static::STATUS_FAILED;
        }
        if ($this->completed) {
            return static::STATUS_COMPLETE;
        }
        return static::STATUS_INPROGRESS;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->boolean()->create('completed', '1', '0'),
            $factory->create('name', Filter::$stringDefaults),
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
            $factory->create('completed'),
            $factory->create('name'),
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
            'completed' => 'completed',
            'resource_id' => 'resource_id',
            'name' => 'name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
