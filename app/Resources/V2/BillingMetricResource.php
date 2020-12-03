<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class BillingMetricResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'resource_id' => $this->resource_id,
            'vpc_id' => $this->vpc_id,
            'reseller_id' => $this->reseller_id,
            'key' => $this->key,
            'value' => $this->value,
            'start' => Carbon::parse(
                $this->start,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'end' => Carbon::parse(
                $this->end,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
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
