<?php

namespace App\Models\V2;

use App\Models\V2\Filters\NullDateFilter;
use App\Support\Resource;
use App\Traits\V2\CustomKey;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Filter;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class BillingMetric
 * @package App\Models\V2
 */
class BillingMetric extends Model implements Searchable
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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'resource_id' => $filter->string(),
            'vpc_id' => $filter->string(),
            'reseller_id' => $filter->numeric(),
            'name' => $filter->string(),
            'key' => $filter->string(),
            'value' => $filter->string(),
            'start' => $filter->wrap(new NullDateFilter)->date(),
            'end' => $filter->wrap(new NullDateFilter)->date(),
            'category' => $filter->string(),
            'price' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
