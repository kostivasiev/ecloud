<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class ResourceTier extends Model implements Searchable, AvailabilityZoneable
{
    use CustomKey, SoftDeletes, DefaultName, HasFactory, DeletionRules;

    public $keyPrefix = 'rt';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'availability_zone_id',
            'active',
        ]);

        $this->casts = [
            'active' => 'boolean'
        ];

        parent::__construct($attributes);
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

        return $query->where('active', '=', true);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'active' => $filter->boolean(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function resourceTierHostGroups()
    {
        return $this->hasMany(ResourceTierHostGroup::class);
    }

    public function hostGroups(): HasManyThrough
    {
        return $this->hasManyThrough(
            HostGroup::class,
            ResourceTierHostGroup::class,
            'resource_tier_id',
            'id',
            'id',
            'host_group_id'
        );
    }
}
