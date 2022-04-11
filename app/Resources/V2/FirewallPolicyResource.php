<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UKFast\Responses\UKFastResource;

/**
 * Class FirewallPolicyResource
 * @package App\Resources\V2
 * @property string id
 * @property string name
 * @property string sequence
 * @property string router_id
 * @property string created_at
 * @property string updated_at
 */
class FirewallPolicyResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $attributes = [
            'id' => $this->id,
            'name' => $this->name,
            'sequence' => $this->sequence,
            'router_id' => $this->router_id,
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
        if (Auth::user()->isAdmin()) {
            $attributes['locked'] = $this->locked;
        }
        return $attributes;
    }
}
