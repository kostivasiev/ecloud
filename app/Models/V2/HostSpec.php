<?php

namespace App\Models\V2;

use App\Models\Scopes\IsNotHidden;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class HostSpec
 * @package App\Models\V2
 */
class HostSpec extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName;

    public string $keyPrefix = 'hs';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'ucs_specification_name',
            'cpu_sockets',
            'cpu_type',
            'cpu_cores',
            'cpu_clock_speed',
            'ram_capacity',
            'is_hidden',
        ]);

        $this->casts = [
            'cpu_sockets' => 'integer',
            'cpu_cores' => 'integer',
            'cpu_clock_speed' => 'integer',
            'ram_capacity' => 'integer',
            'is_hidden' => 'boolean',
        ];

        $this->attributes = [
            'is_hidden' => false,
        ];

        parent::__construct($attributes);
    }

    public function hostGroups()
    {
        return $this->hasMany(HostGroup::class);
    }

    public function availabilityZones()
    {
        return $this->belongsToMany(AvailabilityZone::class);
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->whereFalse('is_hidden');
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'cpu_sockets' => $filter->numeric(),
            'cpu_type' => $filter->string(),
            'cpu_cores' => $filter->numeric(),
            'cpu_clock_speed' => $filter->numeric(),
            'ram_capacity' => $filter->numeric(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
