<?php

namespace App\Models\V2;

use App\Events\V2\Router\Creating;
use App\Events\V2\Router\Deleted;
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

/**
 * Class Router
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 * @method static forUser(string $user)
 * @method static isManagement()
 */
class Router extends Model implements Searchable, ResellerScopeable, Manageable, AvailabilityZoneable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'rtr';

    public $children = [
        'vpns',
        'networks'
    ];

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'vpc_id',
            'availability_zone_id',
            'router_throughput_id',
            'is_management',
        ]);

        $this->casts = [
            'is_management' => 'boolean',
        ];

        $this->attributes = [
            'is_management' => false,
        ];

        $this->dispatchesEvents = [
            'creating' => Creating::class,
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function getResellerId(): int
    {
        return $this->vpc->getResellerId();
    }

    public function availabilityZone()
    {
        return $this->belongsTo(AvailabilityZone::class);
    }

    public function vpns()
    {
        return $this->hasMany(VpnService::class);
    }

    public function firewallPolicies()
    {
        return $this->hasMany(FirewallPolicy::class);
    }

    public function vpc()
    {
        return $this->belongsTo(Vpc::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function routerThroughput()
    {
        return $this->belongsTo(RouterThroughput::class);
    }

    public function isManaged(): bool
    {
        return (bool) $this->attributes['is_management'];
    }

    public function isHidden(): bool
    {
        return $this->isManaged();
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

        $query->where('is_management', false);
        return $query->whereHas('vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    /**
     * @param Builder $query
     * @return Builder
     */
    public function scopeIsManagement(Builder $query)
    {
        return $query->where('is_management', true);
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->configure(fn ($filter) => [
            'id' => $filter->string(),
            'name' => $filter->string(),
            'router_throughput_id' => $filter->string(),
            'vpc_id' => $filter->string(),
            'availability_zone_id' => $filter->string(),
            'created_at' => $filter->date(),
            'updated_at' => $filter->date(),
            'is_management' => $filter->boolean(),
        ]);
    }
}
