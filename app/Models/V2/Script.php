<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Script extends Model implements Searchable
{
    use CustomKey, SoftDeletes, DefaultName, HasFactory;

    public $keyPrefix = 'scr';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'software_id',
            'sequence',
            'script',
        ];
        $this->casts = [
            'sequence' => 'integer'
        ];
        parent::__construct($attributes);
    }

    protected static function booted()
    {
        /**
         * If sequence is not specified when creating, increment to next available.
         */
        static::creating(function ($model) {
            if (empty($model->sequence)) {
                $max = Script::where('software_id', $model->software_id)->pluck('sequence')->max();
                $model->sequence = ++$max;
            }
        });
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
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
        return $query->whereHas('software', function ($query) use ($user) {
            $query->where('visibility', Software::VISIBILITY_PUBLIC);
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'software_id' => $filter->string(),
            'sequence' => $filter->numeric(),
            'script' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
