<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VpnSessionResource
 * @package App\Resources\V2
 * @property string id
 * @property string name
 * @property string vpn_profile_group_id
 * @property string vpn_service_id
 * @property string vpn_endpoint_id
 * @property string remote_ip
 * @property string remote_networks
 * @property string local_networks
 * @property string created_at
 * @property string updated_at
 */
class VpnSessionResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $retVal = [
            'id' => $this->id,
            'name' => $this->name,
            'vpn_profile_group_id' => $this->vpn_profile_group_id,
            'vpn_service_id' => $this->vpn_service_id,
            'vpn_endpoint_id' => $this->vpn_endpoint_id,
            'remote_ip' => $this->remote_ip,
            'remote_networks' => $this->remote_networks,
            'local_networks' => $this->local_networks,
            'sync' => $this->sync,
            'vpc_id' => $this->vpnService ? $this->vpnService->router->vpc->id : null,
        ];
        $retVal['created_at'] = $this->created_at === null ? null : Carbon::parse(
            $this->created_at,
            new \DateTimeZone(config('app.timezone'))
        )->toIso8601String();
        $retVal['updated_at'] = $this->updated_at === null ? null : Carbon::parse(
            $this->updated_at,
            new \DateTimeZone(config('app.timezone'))
        )->toIso8601String();

        if ($request->route('vpnSessionId')) {
            $retVal['tunnel_details'] = $this->tunnel_details;
        }

        return $retVal;
    }
}
