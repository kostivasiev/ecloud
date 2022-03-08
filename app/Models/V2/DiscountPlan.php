<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class DiscountPlan
 * @package App\Models\V2
 * @method static DiscountPlan forUser($user)
 * @method static DiscountPlan findOrFail(string $commitmentId)
 * @property mixed reseller_id
 */
class DiscountPlan extends Model implements Filterable, Sortable
{
    use HasFactory, CustomKey, SoftDeletes;

    public $keyPrefix = 'dplan';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'reseller_id',
        'contact_id',
        'employee_id',
        'orderform_id',
        'name',
        'commitment_amount',
        'commitment_before_discount',
        'discount_rate',
        'term_length',
        'term_start_date',
        'term_end_date',
        'status',
        'response_date',
        'reseller_id',
    ];

    protected $casts = [
        'commitment_amount' => 'float',
        'commitment_before_discount' => 'float',
        'discount_rate' => 'float',
        'term_length' => 'integer',
        'term_start_date' => 'date',
        'term_end_date' => 'datetime',
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
        return $query->where('reseller_id', $user->resellerId());
    }

    /**
     * @return bool
     */
    public function approve() : bool
    {
        $this->attributes['status'] = 'approved';
        $this->attributes['response_date'] = Carbon::now();
        return $this->save();
    }

    /**
     * @return bool
     */
    public function reject() : bool
    {
        $this->attributes['status'] = 'rejected';
        $this->attributes['response_date'] = Carbon::now();
        return $this->save();
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$numericDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->create('contact_id', Filter::$numericDefaults),
            $factory->create('employee_id', Filter::$numericDefaults),
            $factory->create('orderform_id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('commitment_amount', Filter::$numericDefaults),
            $factory->create('commitment_before_discount', Filter::$numericDefaults),
            $factory->create('discount_rate', Filter::$numericDefaults),
            $factory->create('term_length', Filter::$numericDefaults),
            $factory->create('term_start_date', Filter::$dateDefaults),
            $factory->create('term_end_date', Filter::$dateDefaults),
            $factory->create('status', Filter::$stringDefaults),
            $factory->create('response_date', Filter::$dateDefaults),
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
            $factory->create('reseller_id'),
            $factory->create('contact_id'),
            $factory->create('employee_id'),
            $factory->create('orderform_id'),
            $factory->create('name'),
            $factory->create('commitment_amount'),
            $factory->create('commitment_before_discount'),
            $factory->create('discount_rate'),
            $factory->create('term_length'),
            $factory->create('term_start_date'),
            $factory->create('term_end_date'),
            $factory->create('status'),
            $factory->create('response_date'),
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
            'reseller_id' => 'reseller_id',
            'contact_id' => 'contact_id',
            'employee_id' => 'employee_id',
            'orderform_id' => 'orderform_id',
            'name' => 'name',
            'commitment_amount' => 'commitment_amount',
            'commitment_before_discount' => 'commitment_before_discount',
            'discount_rate' => 'discount_rate',
            'term_length' => 'term_length',
            'term_start_date' => 'term_start_date',
            'term_end_date' => 'term_end_date',
            'status' => 'status',
            'response_date' => 'response_date',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
