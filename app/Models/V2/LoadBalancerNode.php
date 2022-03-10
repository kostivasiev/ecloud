<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factory;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class LoadBalancerNode extends Model implements Searchable
{
    use CustomKey, SoftDeletes, HasFactory, Syncable, Taskable;

    public $keyPrefix = 'ln';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'load_balancer_id',
            'instance_id',
            'node_id',
        ];
        parent::__construct($attributes);
    }

    public function instance(): BelongsTo
    {
        return $this->belongsTo(Instance::class);
    }

    public function loadBalancer(): BelongsTo
    {
        return $this->belongsTo(LoadBalancer::class);
    }

    /**
     * @param $query
     * @param Consumer $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query->whereHas('instance.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'load_balancer_id' => $filter->string(),
            'instance_id' => $filter->string(),
            'node_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
