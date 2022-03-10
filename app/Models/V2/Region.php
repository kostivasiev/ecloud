<?php

namespace App\Models\V2;

use App\Events\V2\Region\Creating;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class Region
 * @package App\Models\V2
 * @method static findOrFail(string $vdcUuid)
 * @method static forUser(string $user)
 */
class Region extends Model implements Searchable
{
    use HasFactory, CustomKey, SoftDeletes, DeletionRules;

    public $keyPrefix = 'reg';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    public $children = [
        'availabilityZones',
        'vpcs',
    ];

    public function availabilityZones()
    {
        return $this->hasMany(AvailabilityZone::class);
    }

    public function vpcs()
    {
        return $this->hasMany(Vpc::class);
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

        return $query->where('is_public', '=', 1);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'is_public' => $filter->boolean(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
        ]);
    }
}
