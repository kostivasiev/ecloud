<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class RouterThroughputResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'availability_zone_id' => $this->availability_zone_id,
            'name' => $this->name,
            'committed_bandwidth' => $this->committed_bandwidth,
            'burst_size' => $this->burst_size,
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
