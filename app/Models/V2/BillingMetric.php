<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'bm';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'resource_id',
        'vpc_id',
        'reseller_id',
        'key',
        'value',
        'start',
        'end',
        'category',
        'price',
    ];
    protected $casts = [
        'price' => 'float',
    ];

    public function scopeForUser($query, $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->where('reseller_id', '=', $user->resellerId());
    }

    /**
     * @param $resource
     * @param $key
     * @param string $operator
     * @return BillingMetric|null
     */
    public static function getActiveByKey($resource, $key, $operator = '='): ?BillingMetric
    {
        return self::where('resource_id', $resource->getKey())
            ->whereNull('end')
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
