<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class DiscountPlan
 * @package App\Models\V2
 * @method static DiscountPlan forUser($user)
 * @method static DiscountPlan findOrFail(string $commitmentId)
 * @property mixed reseller_id
 */
class DiscountPlan extends Model implements Searchable
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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'reseller_id' => $filter->numeric(),
            'contact_id' => $filter->numeric(),
            'employee_id' => $filter->numeric(),
            'orderform_id' => $filter->string(),
            'name' => $filter->string(),
            'commitment_amount' => $filter->numeric(),
            'commitment_before_discount' => $filter->numeric(),
            'discount_rate' => $filter->numeric(),
            'term_length' => $filter->numeric(),
            'term_start_date' => $filter->date(),
            'term_end_date' => $filter->date(),
            'status' => $filter->string(),
            'response_date' => $filter->date(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
