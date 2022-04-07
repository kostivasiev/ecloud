<?php

namespace App\Models\V2;

use App\Models\V2\Filters\VpnEndpoint\VpcIdFilter;
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

class VpnEndpoint extends Model implements Searchable, AvailabilityZoneable, ResellerScopeable
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public string $keyPrefix = 'vpne';

    public function __construct(array $attributes = [])
    {
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';

        $this->fillable([
            'id',
            'name',
            'vpn_service_id',
        ]);

        parent::__construct($attributes);
    }

    public function vpnService()
    {
        return $this->belongsTo(VpnService::class);
    }

    public function vpnSessions()
    {
        return $this->hasMany(VpnSession::class);
    }

    public function floatingIp()
    {
        return $this->morphOne(FloatingIp::class, 'resource');
    }

    public function getResellerId(): int
    {
        return $this->vpnService->getResellerId();
    }

    public function availabilityZone()
    {
        return $this->vpnService->router->availabilityZone();
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
        return $query->whereHas('vpnService.router.vpc', function ($query) use ($user) {
            $query->where('reseller_id', $user->resellerId());
        });
    }

    public function sieve(Sieve $sieve)
    {
        $sieve->setDefaultSort('created_at', 'desc')
            ->configure(fn ($filter) => [
                'id' => $filter->string(),
                'name' => $filter->string(),
                'vpn_service_id' => $filter->string(),
                'created_at' => $filter->date(),
                'updated_at' => $filter->date(),
                'vpc_id' => $filter->wrap(new VpcIdFilter)->string(),
            ]);
    }
}
