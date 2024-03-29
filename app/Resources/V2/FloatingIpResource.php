<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class FloatingIpResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string vpc_id
 * @property string name
 * @property string ip_address
 * @property string resource_id
 * @property string rdns_hostname
 * @property string created_at
 * @property string updated_at
 */
class FloatingIpResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vpc_id' => $this->vpc_id,
            'availability_zone_id' => $this->availability_zone_id,
            'ip_address' => $this->ip_address,
            'resource_id' => $this->resource_id,
            'rdns_hostname' => $this->rdns_hostname,
            'sync' => $this->sync,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
