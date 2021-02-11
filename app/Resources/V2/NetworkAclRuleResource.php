<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class NetworkAclRuleResource
 * @package App\Resources\V2
 * @property string id
 * @property string network_acl_policy_id
 * @property string name
 * @property integer sequence
 * @property string source
 * @property string destination
 * @property string action
 * @property boolean enabled
 * @property string created_at
 * @property string updated_at
 */
class NetworkAclRuleResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'network_acl_policy_id' => $this->network_acl_policy_id,
            'name' => $this->name,
            'sequence' => $this->sequence,
            'source' => $this->source,
            'destination' => $this->destination,
            'action' => $this->action,
            'enabled' => $this->enabled,
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
