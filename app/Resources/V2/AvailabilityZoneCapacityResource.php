<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class AvailabilityZoneCapacityResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string availability_zone_id
 * @property string type
 * @property string current
 * @property string alert_warning
 * @property string alert_critical
 * @property string max
 * @property string created_at
 * @property string updated_at
 */
class AvailabilityZoneCapacityResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'availability_zone_id' => $this->availability_zone_id,
            'type' => $this->type,
            'current' => $this->current,
            'alert_warning' => $this->alert_warning,
            'alert_critical' => $this->alert_critical,
            'max' => $this->max,
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
