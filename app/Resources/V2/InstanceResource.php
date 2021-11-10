<?php

namespace App\Resources\V2;

use App\Services\V2\KingpinService;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use UKFast\Responses\UKFastResource;
use Illuminate\Support\Facades\Log;

class InstanceResource extends UKFastResource
{
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
            $kingpinData = null;
            try {
                $kingpinResponse = $this->availabilityZone->kingpinService()->get('/api/v2/vpc/' . $this->vpc_id . '/instance/' . $this->id);

                $kingpinData = json_decode($kingpinResponse->getBody()->getContents());
            } catch (Exception $exception) {
                Log::info('Failed to retrieve instance from Kingpin', [
                    'vpc_id' => $this->vpc_id,
                    'instance_id' => $this->id,
                    'message' => $exception->getMessage()
                ]);
            }
            $response['online'] = isset($kingpinData->powerState) ? $kingpinData->powerState == KingpinService::INSTANCE_POWERSTATE_POWEREDON : null;
            if ($this->is_online !== $response['online']) {
                $this->setAttribute('is_online', $response['online'] ?? false)->saveQuietly();
            }
            $response['agent_running'] = isset($kingpinData->toolsRunningStatus) ? $kingpinData->toolsRunningStatus == KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING : null;
        }
        if (Auth::user()->isAdmin()) {
            $response['is_hidden'] = $this->isHidden();
            $response['load_balancer_id'] = $this->load_balancer_id;
        }

        return $response;
    }
}
