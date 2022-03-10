<?php

namespace App\Models\V2;

use App\Events\V2\Task\Created;
use App\Events\V2\Task\Updated;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class Task extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes;

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

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'completed' => $filter->boolean(),
            'name' => $filter->string(),
            'resource_id' => $filter->string(),
            'reseller_id' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    public function updateData(string $key, $value): bool
    {
        $taskData = $this->data ?? [];
        $taskData[$key] = $value;
        return $this->setAttribute('data', $taskData)->saveQuietly();
    }
}
