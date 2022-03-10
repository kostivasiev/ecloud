<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class VpnProfileGroup extends Model implements Searchable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpnpg';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'description',
            'availability_zone_id',
            'ike_profile_id',
            'ipsec_profile_id'
        ];
        parent::__construct($attributes);
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpnSessions()
    {
        return $this->hasMany(VpnSession::class);
    }

    /**
     * @param Builder $query
     * @param Consumer $user
     * @return Builder
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->whereHas('availabilityZone.region', function ($query) {
            $query->where('is_public', '=', true);
        })->whereHas('availabilityZone', function ($query) {
            $query->where('is_public', '=', true);
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'description' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'ike_profile_id' => $filter->string(),
            'ipsec_profile_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
