<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class AvailabilityZoneCapacityResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string description
 * @property integer node_count
 * @property integer cpu
 * @property integer ram
 * @property integer hdd
 * @property integer iops
 * @property string image_id
 * @property string created_at
 * @property string updated_at
 */
class LoadBalancerSpecificationResource extends UKFastResource
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
            'description' => $this->description,
        ];

        if ($request->user()->isAdmin()) {
            $data['node_count'] = $this->node_count;
            $data['cpu'] = $this->cpu;
            $data['ram'] = $this->ram;
            $data['hdd'] = $this->hdd;
            $data['iops'] = $this->iops;
            $data['image_id'] = $this->image_id;
            $data['created_at'] = Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String();
            $data['updated_at'] = Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String();
        }

        return $data;
    }
}
