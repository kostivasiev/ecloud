<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class LoadBalancerResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string availability_zone_id
 * @property string vpc_id
 * @property string load_balancer_spec_id
 * @property string config_id
 * @property string created_at
 * @property string updated_at
 */
class LoadBalancerResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'availability_zone_id' => $this->availability_zone_id,
            'vpc_id' => $this->vpc_id,
            'load_balancer_spec_id' => $this->load_balancer_spec_id,
            'sync' => $this->sync,
            'config_id' => $this->config_id,
            'network_id' => $this->network_id,
            'nodes' => $this->nodes,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        return $data;
    }
}
