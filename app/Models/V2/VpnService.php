<?php

namespace App\Models\V2;

use App\Events\V2\Vpn\Creating;
use App\Models\V2\Filters\VpcIdFilter;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

/**
 * Class Vpns
 * @package App\Models\V2
 * @method static findOrFail(string $dhcpId)
 * @method static forUser(string $user)
 */
class VpnService extends Model implements Searchable, AvailabilityZoneable, ResellerScopeable, VpcAble
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpn';

    public $children = [
        'vpnSessions',
        'vpnEndpoints',
    ];

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'router_id',
            'name',
        ];
        parent::__construct($attributes);
    }

    protected $dispatchesEvents = [
        'creating' => Creating::class,
    ];

    public function router()
    {
        return $this->belongsTo(Router::class);
    }

    public function vpc()
    {
        return $this->router->vpc();
    }

    public function vpnEndpoints()
    {
        return $this->hasMany(VpnEndpoint::class);
    }

    public function vpnSessions()
    {
        return $this->hasMany(VpnSession::class);
    }

    public function availabilityZone()
    {
        return $this->router->availabilityZone();
    }

    public function getResellerId(): int
    {
        return $this->router->getResellerId();
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
        return $query->whereHas('router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->setDefaultSort('created_at', 'desc')
            ->configure(fn ($filter) => [
                'id' => $filter->string(),
                'router_id' => $filter->string(),
                'name' => $filter->string(),
                'created_at' => $filter->date(),
                'updated_at' => $filter->date(),
                'vpc_id' => $filter->for('router.vpc_id')->string(),
            ]);
    }
}
