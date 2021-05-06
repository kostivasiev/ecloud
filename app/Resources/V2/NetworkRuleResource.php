<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class NetworkRuleResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'network_policy_id' => $this->network_policy_id,
            'name' => $this->name,
            'sequence' => $this->sequence,
            'source' => $this->source,
            'destination' => $this->destination,
            'action' => $this->action,
            'enabled' => $this->enabled,
            'type' => $this->type,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
