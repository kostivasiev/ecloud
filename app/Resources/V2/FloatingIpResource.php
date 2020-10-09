<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class FloatingIpResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string vpc_id
 * @property string ip_address
 * @property string created_at
 * @property string updated_at
 */
class FloatingIpResource extends UKFastResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'vpc_id' => $this->vpc_id,
            'ip_address' => $this->ip_address,
            'created_at' => Carbon::parse(
                $this->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
