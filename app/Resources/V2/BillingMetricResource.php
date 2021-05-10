<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class BillingMetricResource extends UKFastResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'resource_id' => $this->resource_id,
            'vpc_id' => $this->vpc_id,
            'key' => $this->key,
            'value' => (float) $this->value,
            'category' => $this->category,
            'price' => (float) $this->price,
            'start' => $this->start === null ? null : Carbon::parse(
                $this->start,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'end' => $this->end === null ? null : Carbon::parse(
                $this->end,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
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
            $data['reseller_id'] = $this->reseller_id;
        }

        return $data;
    }
}
