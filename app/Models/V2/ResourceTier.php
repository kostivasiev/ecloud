<?php

namespace App\Models\V2;

use App\Services\V2\KingpinService;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
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

    /**
     * Get the capacities for the host groups in the resource tier,
     * sorted by percentage used capacity (RAM) lowest -> highest.
     * @return Collection|null
     */
    public function getHostGroupCapacities(): ?Collection
    {
        try {
            $response = $this->availabilityZone->kingpinService()->post(
                KingpinService::SHARED_HOST_GROUP_CAPACITY,
                [
                    'json' => [
                        // TODO: Maps to current cluster names, replace with $this->hostGroups->pluck('id')->toArray()
                        // when the clusters are renamed
                        'hostGroupIds' => $this->hostGroups->pluck('id')->map(function ($id) {
                            return HostGroup::mapId($id);
                        })->toArray(),
                    ],
                ]
            );
            $response = json_decode($response->getBody()->getContents());
        } catch (\Exception $e) {
            Log::error('Unable to retrieve host group capacities', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }

        // Sort by percentage used capacity
        return collect($response)->map(function ($hostGroup) {
            return HostGroup::formatHostGroupCapacity($hostGroup);
        })->sortBy([
            fn ($a, $b) => $a['ram']['percentage'] <=> $b['ram']['percentage'],
        ]);
    }

    /**
     * Return the least utilised host group assigned to the resource tier
     * @return HostGroup
     */
    public function getDefaultHostGroup(): HostGroup
    {
        return HostGroup::find($this->getHostGroupCapacities()->first()['id']);
    }
}
