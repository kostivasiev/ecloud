<?php
namespace App\Traits\V2;

use App\Models\V2\Instance;
use App\Resources\V2\InstanceResource;
use App\Services\V2\KingpinService;
use Exception;
use Illuminate\Support\Facades\Log;

trait InstanceOnlineState
{
    public function getOnlineStatus($instance, array $retVal = []): array
    {
        $kingpinData = null;
        try {
            $kingpinResponse = $instance->availabilityZone
                ->kingpinService()
                ->get('/api/v2/vpc/' . $instance->vpc_id . '/instance/' . $instance->id);
            $kingpinData = json_decode($kingpinResponse->getBody()->getContents());
        } catch (Exception $exception) {
            Log::info('Failed to retrieve instance from Kingpin', [
                'vpc_id' => $instance->vpc_id,
                'instance_id' => $instance->id,
                'message' => $exception->getMessage()
            ]);
        }
        $retVal['online'] = isset($kingpinData->powerState) ? $kingpinData->powerState == KingpinService::INSTANCE_POWERSTATE_POWEREDON : null;
        $retVal['agent_running'] = isset($kingpinData->toolsRunningStatus) ? $kingpinData->toolsRunningStatus == KingpinService::INSTANCE_TOOLSRUNNINGSTATUS_RUNNING : null;
        return $retVal;
    }
}
