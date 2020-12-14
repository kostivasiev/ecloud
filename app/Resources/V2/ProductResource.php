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
            'category' => $this->product_subcategory,
            'price' => $this->getPrice(app('request')->user->resellerId)
        ];
    }
}


