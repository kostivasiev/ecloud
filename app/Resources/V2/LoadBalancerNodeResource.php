<?php

namespace App\Resources\V2;

use App\Services\V2\KingpinService;
use App\Traits\V2\InstanceOnlineState;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UKFast\Responses\UKFastResource;
use Illuminate\Support\Facades\Log;

class LoadBalancerNodeResource extends UKFastResource
{
    use InstanceOnlineState;

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            'id' => $this->instance->id,
            'name' => $this->instance->name,
            'vpc_id' => $this->instance->vpc_id,
            'availability_zone_id' => $this->instance->availability_zone_id,
            'image_id' => $this->instance->image_id,
            'vcpu_cores' => $this->instance->vcpu_cores,
            'ram_capacity' => $this->instance->ram_capacity,
            'locked' => $this->instance->locked,
            'platform' => $this->instance->platform,
            'backup_enabled' => $this->instance->backup_enabled,
            'host_group_id' => !empty($this->instance->host_group_id) ? $this->instance->host_group_id : null,
            'volume_group_id' => !empty($this->instance->volume_group_id) ? $this->instance->volume_group_id : null,
            'volume_capacity' => $this->instance->volume_capacity,
            'sync' => $this->instance->sync,
            'created_at' => $this->instance->created_at === null ? null : Carbon::parse(
                $this->instance->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->instance->updated_at === null ? null : Carbon::parse(
                $this->instance->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
        if ($request->route('instanceId')) {
            $response = $this->getOnlineStatus($this->instance, $response);
        }
        if (Auth::user()->isAdmin()) {
            $response['is_hidden'] = $this->instance->isHidden();
            $response['load_balancer_id'] = $this->load_balancer_id;
        }

        return $response;
    }
}
