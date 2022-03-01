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
 * @property string source
 * @property string destination
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
            'sequence' => $this->sequence,
            'firewall_policy_id' => $this->firewall_policy_id,
            'source' => $this->source,
            'destination' => $this->destination,
            'action' => $this->action,
            'direction' => $this->direction,
            'enabled' => $this->enabled,
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
