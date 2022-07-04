<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UKFast\Responses\UKFastResource;

class ResourceTierResource extends UKFastResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'availability_zone_id' => $this->availability_zone_id,
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        if (Auth::user()->isAdmin()) {
            $data['active'] = $this->active;
        }

        return $data;
    }
}
