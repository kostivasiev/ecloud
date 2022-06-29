<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class HostSpecResource extends UKFastResource
{
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'cpu_sockets' => $this->cpu_sockets,
            'cpu_type' => $this->cpu_type,
            'cpu_cores' => $this->cpu_cores,
            'cpu_clock_speed' => $this->cpu_clock_speed,
            'ram_capacity' => $this->ram_capacity,
        ];

        if ($request->user()->isAdmin()) {
            $data['is_hidden'] = $this->is_hidden;
            $data['ucs_specification_name'] = $this->ucs_specification_name;
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
