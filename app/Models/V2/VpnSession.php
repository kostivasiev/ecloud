<?php

namespace App\Models\V2;

use App\Events\V2\VpnSession\Deleted;
use App\Models\V2\Filters\VpcIdFilter;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\Sieve\Searchable;
use UKFast\Sieve\Sieve;

class VpnSession extends Model implements Searchable, AvailabilityZoneable, ResellerScopeable, VpcAble
{
    use HasFactory, CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    const CREDENTIAL_PSK_USERNAME = 'PSK';

    public $keyPrefix = 'vpns';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'vpn_profile_group_id',
            'vpn_service_id',
            'vpn_endpoint_id',
            'remote_ip',
        ];

        $this->dispatchesEvents = [
            'deleted' => Deleted::class,
        ];

        parent::__construct($attributes);
    }

    public function vpnProfileGroup()
    {
        return $this->belongsTo(VpnProfileGroup::class);
    }

    public function vpnService()
    {
        return $this->belongsTo(VpnService::class);
    }

    public function vpnEndpoint()
    {
        return $this->belongsTo(VpnEndpoint::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }

    public function availabilityZone()
    {
        return $this->vpnService->router->availabilityZone();
    }

    public function vpc()
    {
        return $this->vpnService->router->vpc();
    }

    public function vpnSessionNetworks()
    {
        return $this->hasMany(VpnSessionNetwork::class);
    }

    public function getResellerId(): int
    {
        return $this->vpnService->getResellerId();
    }

    public function getPskAttribute()
    {
        return ($this->credentials()->where('username', self::CREDENTIAL_PSK_USERNAME)->exists()) ?
            $this->credentials()->where('username', self::CREDENTIAL_PSK_USERNAME)->pluck('password')->first() :
            null;
    }

    public function getLocalNetworksAttribute()
    {
        return $this->getNetworksAttributeByType(VpnSessionNetwork::TYPE_LOCAL);
    }

    public function getRemoteNetworksAttribute()
    {
        return $this->getNetworksAttributeByType(VpnSessionNetwork::TYPE_REMOTE);
    }

    protected function getNetworksAttributeByType($type)
    {
        return $this->getNetworksByType($type)->get()->pluck('ip_address')->join(',');
    }

    public function getNetworksByType($type)
    {
        return $this->vpnSessionNetworks()->where('type', '=', $type);
    }

    public function getTunnelDetailsAttribute()
    {
        try {
            $response = $this->availabilityZone->nsxService()->get('/policy/api/v1/infra/tier-1s/' . $this->vpnService->router->id . '/locale-services/' . $this->vpnService->router->id . '/ipsec-vpn-services/' . $this->vpnService->id . '/sessions/' . $this->id . '/statistics');
            $responseData = json_decode($response->getBody()->getContents());

            if (empty($responseData->results)) {
                return null;
            }

            $result = (object)[
                'session_state' => (isset($responseData->results[0]->ike_status->ike_session_state)) ? $responseData->results[0]->ike_status->ike_session_state : null,
                'tunnel_statistics' => [],
            ];

            if (!isset($responseData->results[0]->policy_statistics[0]->tunnel_statistics)) {
                return $result;
            }

            foreach ($responseData->results[0]->policy_statistics[0]->tunnel_statistics as $tunnelStatistic) {
                $result->tunnel_statistics[] = (object)[
                    'tunnel_status' => $tunnelStatistic->tunnel_status ?? null,
                    'tunnel_down_reason' => $tunnelStatistic->tunnel_down_reason ?? null,
                    'local_subnet' => $tunnelStatistic->local_subnet ?? null,
                    'peer_subnet' => $tunnelStatistic->peer_subnet ?? null,
                ];
            }

            return $result;
        } catch (\Exception $exception) {
            Log::warning('Failed to retrieve tunnel status from NSX', [
                'vpn_session_id' => $this->id,
                'message' => $exception->getMessage()
            ]);
        }

        return null;
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
                'vpn_profile_group_id' => $filter->string(),
                'vpn_service_id' => $filter->string(),
                'vpn_endpoint_id' => $filter->string(),
                'remote_ip' => $filter->string(),
                'remote_networks' => $filter->string(),
                'local_networks' => $filter->string(),
                'created_at' => $filter->date(),
                'updated_at' => $filter->date(),
                'vpc_id' => $filter->wrap(new VpcIdFilter($this))->string(),
            ]);
    }

    public function updatePskCredential(string $psk): Credential
    {
        $credential = $this?->credentials()
            ->where('username', VpnSession::CREDENTIAL_PSK_USERNAME)
            ->first();
        if (!$credential) {
            $credential = new Credential(
                [
                    'name' => 'Pre-shared Key for VPN Session ' . $this->id,
                    'host' => null,
                    'username' => VpnSession::CREDENTIAL_PSK_USERNAME,
                    'password' => $psk,
                    'port' => null,
                    'is_hidden' => true,
                ]
            );
        } else {
            $credential->password = $psk;
        }

        $credential->save();
        
        return $credential;
    }
}
