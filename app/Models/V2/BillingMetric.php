<?php

namespace App\Models\V2;

use App\Support\Resource;
use App\Traits\V2\CustomKey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

/**
 * Class BillingMetric
 * @package App\Models\V2
 */
class BillingMetric extends Model implements Filterable, Sortable
{
    use HasFactory, CustomKey, SoftDeletes;

    public $keyPrefix = 'bm';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'resource_id',
            'vpc_id',
            'name',
            'reseller_id',
            'key',
            'value',
            'start',
            'end',
            'category',
            'price',
        ]);

        $this->casts = [
            'price' => 'float',
            'value' => 'float'
        ];

        parent::__construct($attributes);
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->start)) {
                $model->start = Carbon::now();
            }
        });
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class, 'resource_id', 'id');
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function getResource()
    {
        $class = Resource::classFromId($this->resource_id);
        if (empty($class)) {
            return false;
        }

        return $class::find($this->resource_id) ?? false;
    }

    /**
     * @param $resource
     * @param $key
     * @param string $operator
     * @return BillingMetric|null
     */
    public static function getActiveByKey($resource, $key, $operator = '=', $includeFuture = false): ?BillingMetric
    {
        return self::where('resource_id', $resource->id)
            ->where(function ($model) {
                return $model->where('end', '>', Carbon::now())
                    ->orWhereNull('end');
            })
            ->where('key', $operator, $key)
            ->first();
    }

    /**
     * Set the end date/time for a metric
     * @param null $time
     * @return bool
     */
    public function setEndDate($time = null)
    {
        if (empty($time)) {
            $time = Carbon::now();
        }

        $this->attributes['end'] = $time;
        return $this->save();
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('resource_id', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('key', Filter::$stringDefaults),
            $factory->create('value', Filter::$stringDefaults),
            $factory->create('start', Filter::$dateDefaults),
            $factory->create('end', Filter::$dateDefaults),
            $factory->create('category', Filter::$stringDefaults),
            $factory->create('price', Filter::$numericDefaults),
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
            $factory->create('resource_id'),
            $factory->create('vpc_id'),
            $factory->create('reseller_id'),
            $factory->create('name'),
            $factory->create('key'),
            $factory->create('value'),
            $factory->create('start'),
            $factory->create('end'),
            $factory->create('category'),
            $factory->create('price'),
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
            'resource_id' => 'resource_id',
            'vpc_id' => 'vpc_id',
            'reseller_id' => 'reseller_id',
            'name' => 'name',
            'key' => 'key',
            'value' => 'value',
            'start' => 'start',
            'end' => 'end',
            'category' => 'category',
            'price' => 'price',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
