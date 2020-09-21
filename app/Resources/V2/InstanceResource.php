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
 * @property string vcpu_tier
 * @property integer vcpu_cores
 * @property integer ram_capacity
 * @property boolean locked
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
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'vpc_id'       => $this->vpc_id,
            'appliance_id' => $this->appliance_id,
            'vcpu_tier'    => $this->vcpu_tier,
            'vcpu_cores'   => $this->vcpu_count,
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
    }
}
