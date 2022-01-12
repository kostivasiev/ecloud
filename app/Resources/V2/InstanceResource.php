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

class InstanceResource extends UKFastResource
{
    use InstanceOnlineState;

    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $response = [
            'id' => $this->id,
            'name' => $this->name,
            'vpc_id' => $this->vpc_id,
            'availability_zone_id' => $this->availability_zone_id,
            'image_id' => $this->image_id,
            'vcpu_cores' => $this->vcpu_cores,
            'ram_capacity' => $this->ram_capacity,
            'locked' => $this->locked,
            'platform' => $this->platform,
            'backup_enabled' => $this->backup_enabled,
            'host_group_id' => !empty($this->host_group_id) ? $this->host_group_id : null,
            'volume_group_id' => !empty($this->volume_group_id) ? $this->volume_group_id : null,
            'volume_capacity' => $this->volume_capacity,
            'sync' => $this->sync,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
        if ($request->route('instanceId')) {
            $response = $this->getOnlineStatus($this, $response);
        }
        if (Auth::user()->isAdmin()) {
            $response['is_hidden'] = $this->isHidden();
            $response['load_balancer_id'] = $this->loadBalancerNode->load_balancer_id;
        }

        return $response;
    }
}
