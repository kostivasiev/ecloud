<?php

namespace App\Models\V2;

use App\Events\V2\DiscountPlan\Created;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'disc';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';

    protected $fillable = [
        'id',
        'reseller_id',
        'contact_id',
        'employee_id',
        'name',
        'commitment_amount',
        'commitment_before_discount',
        'discount_rate',
        'term_length',
        'term_start_date',
        'term_end_date',
        'pending',
        'approved',
    ];

    protected $casts = [
        'commitment_amount' => 'float',
        'commitment_before_discount' => 'float',
        'discount_rate' => 'float',
        'term_length' => 'integer',
        'term_start_date' => 'datetime',
        'term_end_date' => 'datetime',
        'pending' => 'datetime',
        'approved' => 'datetime',
    ];

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, $user)
    {
        if (!empty($user->resellerId)) {
            $resellerId = filter_var($user->resellerId, FILTER_SANITIZE_NUMBER_INT);
            if (!empty($resellerId)) {
                $query->where('reseller_id', '=', $resellerId);
            }
        }
        return $query;
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
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('commitment_amount', Filter::$numericDefaults),
            $factory->create('commitment_before_discount', Filter::$numericDefaults),
            $factory->create('discount_rate', Filter::$numericDefaults),
            $factory->create('term_length', Filter::$numericDefaults),
            $factory->create('term_start_date', Filter::$dateDefaults),
            $factory->create('term_end_date', Filter::$dateDefaults),
            $factory->create('pending', Filter::$dateDefaults),
            $factory->create('approved', Filter::$dateDefaults),
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
            $factory->create('name'),
            $factory->create('commitment_amount'),
            $factory->create('commitment_before_discount'),
            $factory->create('discount_rate'),
            $factory->create('term_length'),
            $factory->create('term_start_date'),
            $factory->create('term_end_date'),
            $factory->create('pending'),
            $factory->create('approved'),
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
            'name' => 'name',
            'commitment_amount' => 'commitment_amount',
            'commitment_before_discount' => 'commitment_before_discount',
            'discount_rate' => 'discount_rate',
            'term_length' => 'term_length',
            'term_start_date' => 'term_start_date',
            'term_end_date' => 'term_end_date',
            'pending' => 'pending',
            'accepted' => 'approved',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
