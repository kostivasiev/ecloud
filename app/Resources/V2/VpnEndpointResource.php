<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class VpnEndpointResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vpn_service_id' => $this->vpn_service_id,
            'floating_ip_id' => $this->floatingIpResource()->exists() ? $this->floatingIpResource->floatingIp->id : null,
            'sync' => $this->sync,
            'vpc_id' => $this->vpnService ? $this->vpnService->router->vpc->id : null,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
