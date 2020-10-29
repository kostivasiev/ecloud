<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class FirewallRuleResource
 * @package App\Resources\V2
 * @property string id
 * @property string name
 * @property string firewall_policy_id
 * @property string router_id
 * @property string service_type
 * @property string deployed
 * @property string source
 * @property string source_ports
 * @property string destination
 * @property string destination_ports
 * @property string action
 * @property string direction
 * @property string enabled
 * @property string created_at
 * @property string updated_at
 */
class FirewallRuleResource extends UKFastResource
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
            'firewall_policy_id' => $this->firewall_policy_id,
            'router_id' => $this->router_id,
            'service_type' => $this->service_type,
            'source' => $this->source,
            'source_ports' => $this->source_ports,
            'destination' => $this->destination,
            'destination_ports' => $this->destination_ports,
            'action' => $this->action,
            'direction' => $this->direction,
            'enabled' => $this->enabled,
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
