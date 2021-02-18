<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class NetworkRulePortResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'network_rule_id' => $this->network_rule_id,
            'protocol' => $this->protocol,
            'source' => $this->source,
            'destination' => $this->destination,
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
