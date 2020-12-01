<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class NetworksResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string router_id
 * @property string subnet
 * @property string availability_zone_id
 * @property string created_at
 * @property string updated_at
 */
class NetworkResource extends UKFastResource
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
            'router_id' => $this->router_id,
            'subnet' => $this->subnet,
            'sync' => $this->getStatus(),
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
