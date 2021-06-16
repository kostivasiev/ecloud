<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VpnsResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string router_id
 * @property string availability_zone_id
 * @property string created_at
 * @property string updated_at
 */
class VpnServiceResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'router_id' => $this->router_id,
            'availability_zone_id' => $this->availability_zone_id,
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
