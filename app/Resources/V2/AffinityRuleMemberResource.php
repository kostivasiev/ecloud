<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class AffinityRuleMemberResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'rule_id' => $this->rule_id,
            'instance_id' => $this->instance_id,
            'sync' => $this->sync,
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