<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class SupportResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string vpc_id
 * @property string start_date
 * @property string end_date
 * @property string created_at
 * @property string updated_at
 */
class VpcSupportResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vpc_id' => $this->vpc_id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'active' => $this->active,
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
