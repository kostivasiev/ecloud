<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UKFast\Responses\UKFastResource;

/**
 * Class RouterResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string vpc_id
 * @property string created_at
 * @property string updated_at
 */
class RouterResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data =  [
            'id' => $this->id,
            'name' => $this->name,
            'vpc_id' => $this->vpc_id,
            'router_throughput_id' => $this->router_throughput_id,
            'availability_zone_id' => $this->availability_zone_id,
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

        if (Auth::user()->isAdmin()) {
            $data['is_management'] = $this->is_management;
            $data['is_hidden'] = $this->is_hidden;
        }

        return $data;
    }
}
