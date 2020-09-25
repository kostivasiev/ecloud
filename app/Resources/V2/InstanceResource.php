<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class InstanceResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string vpc_id
 * @property string appliance_id
 * @property string appliance_version_id
 * @property integer vcpu_cores
 * @property integer ram_capacity
 * @property string availability_zone_id
 * @property boolean locked
 * @property string power_state
 * @property string created_at
 * @property string updated_at
 */
class InstanceResource extends UKFastResource
{
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            'id'           => $this->id,
            'name'         => $this->name,
            'vpc_id'       => $this->vpc_id,
            'availability_zone_id' => $this->availability_zone_id,
            'appliance_id' => $this->appliance_id,
            'vcpu_cores'   => $this->vcpu_cores,
            'ram_capacity' => $this->ram_capacity,
            'locked'       => $this->locked,
            'created_at'   => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at'   => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
        if ($request->user->isAdministrator) {
            $response['appliance_version_id'] = $this->appliance_version_id;
        }
        if ($request->route('instanceId')) {
            $response['power_state'] = $this->power_state;
        }
        return $response;
    }
}
