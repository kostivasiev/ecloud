<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class FirewallRulePortResource
 * @package App\Resources\V2
 * @property string id
 * @property string name
 * @property string firewall_rule_id
 * @property string protocol
 * @property string source
 * @property string destination
 * @property string created_at
 * @property string updated_at
 */
class FirewallRulePortResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'firewall_rule_id' => $this->firewall_rule_id,
            'protocol' => $this->protocol,
            'source' => $this->source,
            'destination' => $this->destination,
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
