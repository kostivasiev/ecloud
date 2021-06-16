<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class HostGroupResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'vpc_id' => $this->vpc_id,
            'availability_zone_id' => $this->availability_zone_id,
            'host_spec_id' => $this->host_spec_id,
            'windows_enabled' => $this->windows_enabled,
            'usage' => [
                'hosts' => $this->hosts->count(),
                'ram' => [
                    'capacity' => $this->ram_capacity,
                    'reserved' => $this->ram_reserved,
                    'used' => $this->ram_used,
                    'available' => $this->ram_available,
                ],
                'vcpu' => [
                    'capacity' => $this->vcpu_capacity,
                    'used' => $this->vcpu_used,
                    'available' => $this->vcpu_available,
                ]
            ],
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
    }
}
