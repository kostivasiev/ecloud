<?php

namespace App\Resources\V2;

use UKFast\Responses\UKFastResource;

class ProductResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'availability_zone_id' => $this->availabilityZoneId,
            'name' => $this->name,
            'category' => strtolower($this->category),
            'price' => $this->getPrice(app('request')->user->resellerId),
            'rate' => strtolower($this->rate),
        ];
    }
}
