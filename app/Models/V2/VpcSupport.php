<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VpcSupport extends Model implements Filterable, Sortable
{
    use HasFactory, CustomKey, SoftDeletes;

    protected $table = 'vpc_support';
    public $keyPrefix = 'sup';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'vpc_id',
        'start_date',
        'end_date',
    ];

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

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
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function getActiveAttribute()
    {
        if (is_null($this->start_date)) {
            // no start date yet
            return false;
        }

        if (strtotime($this->start_date) > time()) {
            // start date is in the future
            return false;
        }

        // start date is in the past

        if (is_null($this->end_date)) {
            // no end date yet
            return true;
        }

        if (strtotime($this->end_date) < time()) {
            // end date is in the past
            return false;
        }

        if (strtotime($this->end_date) > time()) {
            // end date is in the future
            return true;
        }

        //should never hit this point but just in case
        return false;
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('vpc_id', Filter::$stringDefaults),
            $factory->create('start_date', Filter::$dateDefaults),
            $factory->create('end_date', Filter::$dateDefaults),
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
            $factory->create('vpc_id'),
            $factory->create('start_date'),
            $factory->create('end_date'),
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
            'vpc_id' => 'vpc_id',
            'start_date' => 'start_date',
            'end_date' => 'end_date',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
