<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class NetworkPolicy extends Model implements Searchable, ResellerScopeable, Manageable
{
    use HasFactory, CustomKey, DefaultName, SoftDeletes, Syncable, Taskable;

    public string $keyPrefix = 'np';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'network_id',
            'name',
        ];
        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->network->getResellerId();
    }

    public function network()
    {
        return $this->belongsTo(Network::class);
    }

    public function networkRules()
    {
        return $this->hasMany(NetworkRule::class);
    }

    public function scopeForUser($query, Consumer $user)
    {
        if (!$user->isScoped()) {
            return $query;
        }
        return $query
            ->whereHas('network.router.vpc', function ($query) use ($user) {
                $query->where('reseller_id', $user->resellerId());
            })
            ->whereHas('network.router', function ($query) {
                $query->where('is_management', false);
            });
    }

    public function isManaged() :bool
    {
        return (bool) $this->network->router->isManaged();
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'network_id' => $filter->string(),
            'name' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
