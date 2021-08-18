<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VpnProfileGroupResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string description
 * @property string availability_zone_id
 * @property string ike_profile_id
 * @property string ipsec_profile_id
 * @property string dpd_profile_id
 * @property string created_at
 * @property string updated_at
 */
class VpnProfileGroupResource extends UKFastResource
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
            'availability_zone_id' => $this->availability_zone_id,
        ];
        if ($request->user()->isAdmin()) {
            $data['ike_profile_id'] = $this->ike_profile_id;
            $data['ipsec_profile_id'] = $this->ipsec_profile_id;
            $data['dpd_profile_id'] = $this->dpd_profile_id;
        }
        $data['created_at'] = $this->created_at === null ? null : Carbon::parse(
            $this->created_at,
            new \DateTimeZone(config('app.timezone'))
        )->toIso8601String();
        $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse(
            $this->updated_at,
            new \DateTimeZone(config('app.timezone'))
        )->toIso8601String();
        return $data;
    }
}
