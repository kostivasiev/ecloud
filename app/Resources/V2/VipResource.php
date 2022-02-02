<?php

namespace App\Resources\V2;

use App\Models\V2\IpAddress;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VipResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string load_balancer_id
 * @property string network_id
 * @property string deleted_at
 * @property string created_at
 * @property string updated_at
 */
class VipResource extends UKFastResource
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
            'load_balancer_network_id' => $this->load_balancer_network_id,
            'ip_address_id' => $this->ip_address_id,
//            'floating_ip_id' => $this->floating_ip_id,
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

        if ($request->user()->isAdmin()) {
            $data['config_id'] = $this->config_id;
        }

        return $data;
    }
}
