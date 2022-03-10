<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class VpcSupport extends Model implements Searchable
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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'vpc_id' => $filter->string(),
            'start_date' => $filter->string(),
            'end_date' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
