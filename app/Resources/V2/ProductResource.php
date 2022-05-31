<?php

namespace App\Resources\V2;

use Illuminate\Support\Facades\Auth;
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
            'description' => $this->product_description,
            'category' => strtolower($this->category),
            'price' => $this->getPrice(Auth::user()->resellerId()),
            'rate' => strtolower($this->rate),
        ];
    }
}
