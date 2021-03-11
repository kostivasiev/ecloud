<?php

namespace App\Resources\V2;

use App\Services\V2\KingpinService;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;
use Log;

/**
 * Class InstanceResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string vpc_id
 * @property string appliance_id
 * @property string appliance_version_id
 * @property integer vcpu_cores
 * @property integer ram_capacity
 * @property string availability_zone_id
 * @property boolean locked
 * @property string online
 * @property string agent_running
 * @property string platform
 * @property integer volume_capacity
 * @property boolean backup_enabled
 * @property string status
 * @property string created_at
 * @property string updated_at
 */
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
            'volume_capacity' => $this->volume_capacity,
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
            $response['agent_running'] = isset($kingpinData->toolsRunningStatus) ? $kingpinData->toolsRunningStatus == KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING : null;
        }
        return $response;
    }
}
