<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class LoadBalancerNetwork extends Model implements Searchable
{
    use CustomKey, SoftDeletes, DefaultName, HasFactory, Syncable, Taskable, DeletionRules;

    public $keyPrefix = 'lbn';

    public $children = [
        'vips',
    ];

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->table = 'load_balancer_network';
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'load_balancer_id',
            'network_id',
        ];
        parent::__construct($attributes);
    }

    public function loadBalancer(): BelongsTo
    {
        return $this->belongsTo(LoadBalancer::class);
    }

    public function network(): BelongsTo
    {
        return $this->belongsTo(Network::class);
    }

    public function vips(): HasMany
    {
        return $this->hasMany(Vip::class);
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
        return $query->whereHas('loadBalancer.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'load_balancer_id' => $filter->string(),
            'network_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
