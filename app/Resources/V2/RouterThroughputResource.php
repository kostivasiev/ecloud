<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class RouterThroughputResource extends UKFastResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'availability_zone_id' => $this->availability_zone_id,
            'name' => $this->name,
            'committed_bandwidth' => $this->committed_bandwidth,
        ];

        if ($request->user()->isAdmin()) {
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
