<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VolumeResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string vpc_id
 * @property string availability_zone_id
 * @property string capacity
 * @property string iops
 * @property string vmware_uuid
 * @property boolean type
 * @property string created_at
 * @property string updated_at
 */
class VolumeResource extends UKFastResource
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
            'vpc_id' => $this->vpc_id,
            'availability_zone_id' => $this->availability_zone_id,
            'capacity' => $this->capacity,
            'iops' => $this->iops,
            'attached' => $this->attached,
            'sync' => $this->sync,
            'task' => $this->task,
            'type' => $this->type,
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
            $data['vmware_uuid'] = $this->vmware_uuid;
        }

        return $data;
    }
}
