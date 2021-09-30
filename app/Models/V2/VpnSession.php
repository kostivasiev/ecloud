<?php

namespace App\Models\V2;

use App\Events\V2\VpnSession\Deleted;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VpnSession extends Model implements Filterable, Sortable, AvailabilityZoneable, ResellerScopeable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

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

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('vpn_profile_group_id', Filter::$stringDefaults),
            $factory->create('vpn_service_id', Filter::$stringDefaults),
            $factory->create('vpn_endpoint_id', Filter::$stringDefaults),
            $factory->create('remote_ip', Filter::$stringDefaults),
            $factory->create('remote_networks', Filter::$stringDefaults),
            $factory->create('local_networks', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('vpn_profile_group_id'),
            $factory->create('vpn_service_id'),
            $factory->create('vpn_endpoint_id'),
            $factory->create('remote_ip'),
            $factory->create('remote_networks'),
            $factory->create('local_networks'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'vpn_profile_group_id' => 'vpn_profile_group_id',
            'vpn_service_id' => 'vpn_service_id',
            'vpn_endpoint_id' => 'vpn_endpoint_id',
            'remote_ip' => 'remote_ip',
            'remote_networks' => 'remote_networks',
            'local_networks' => 'local_networks',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
