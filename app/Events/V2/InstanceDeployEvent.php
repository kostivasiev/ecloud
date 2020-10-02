<?php

namespace App\Events\V2;

use App\Events\V2\Data\InstanceDeployEventData;
use Illuminate\Queue\SerializesModels;

class InstanceDeployEvent
{
    use SerializesModels;

    /**
     * @var InstanceDeployEventData
     */
    public $instanceDeployEventData;

    public function __construct(InstanceDeployEventData $instanceDeployEventData)
    {
        $this->instanceDeployEventData = $instanceDeployEventData;
    }
}
