<?php

namespace App\Resources\V2;

use UKFast\Responses\UKFastResource;

/**
 * Class AvailabilityZonesResource
 * @package App\Http\Resources\V2
 * @property string availabilityZoneId
 * @property array compute
 * @property array storage
 * @property array license
 * @property array networking
 */
class AvailabilityZonePricesResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'availability_zone_id' => $this->availabilityZoneId,
            'compute' => $this->compute,
            'storage' => $this->storage,
            'license' => $this->license,
            'networking' => $this->networking,
        ];
    }
}
