<?php
namespace App\Traits\V2\Jobs\Nsx;

use App\Models\V2\AvailabilityZone;
use Illuminate\Database\Eloquent\Model;

trait AwaitRealizedState
{
    protected function awaitRealizedState(Model $model, AvailabilityZone $availabilityZone, $intentPath)
    {
        $response = $availabilityZone->nsxService()->get(
            'policy/api/v1/infra/realized-state/status?intent_path=' . $intentPath
        );

        $response = json_decode($response->getBody()->getContents());
        if ($response->publish_status !== 'REALIZED') {
            $this->info(
                'Waiting for ' . $model->id . ' being deployed, retrying in ' . $this->backoff . ' seconds'
            );
            $this->release($this->backoff);
        }
    }
}
