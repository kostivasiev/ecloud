<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class NicResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string mac_address
 * @property string instance_id
 * @property string network_id
 * @property string ip_address
 * @property string created_at
 * @property string updated_at
 */
class NicResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'mac_address' => $this->mac_address,
            'instance_id' => $this->instance_id,
            'network_id' => $this->network_id,
            'ip_address' => $this->ip_address,
            'sync' => $this->sync,
            'task' => $this->task,
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
