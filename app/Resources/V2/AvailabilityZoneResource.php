<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class AvailabilityZoneResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'datacentre_site_id' => $this->datacentre_site_id,
            'region_id' => $this->region_id
        ];

        if ($request->user()->isAdmin()) {
            $data['is_public'] = $this->is_public;
            $data['nsx_manager_endpoint'] = $this->nsx_manager_endpoint;
            $data['san_name'] = $this->san_name;
            $data['ucs_compute_name'] = $this->ucs_compute_name;

            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
