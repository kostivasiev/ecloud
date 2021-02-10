<?php

namespace App\Resources\V2;

use DateTimeZone;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class AclPolicyResource
 * @package App\Resources\V2
 * @property string id
 * @property string network_id
 * @property string vpc_id
 * @property string name
 * @property string created_at
 * @property string updated_at
 */
class AclPolicyResource extends UKFastResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'network_id' => $this->network_id,
            'vpc_id' => $this->vpc_id,
            'name' => $this->name,
            'sync' => $this->getStatus(),
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