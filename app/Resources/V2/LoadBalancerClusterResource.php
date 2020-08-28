<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class LoadBalancerClusterResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string availability_zone_id
 * @property string vpc_id
 * @property string nodes
 * @property string config_id
 * @property string created_at
 * @property string updated_at
 */
class LoadBalancerClusterResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id'         => $this->id,
            'name'       => $this->name,
            'availability_zone_id' => $this->availability_zone_id,
            'vpc_id' => $this->vpc_id,
            'nodes' => $this->nodes,
            'config_id' => $this->config_id,
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        return $data;
    }
}
