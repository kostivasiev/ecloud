<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class AvailabilityZonesResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string code
 * @property string name
 * @property int site_id
 * @property int region_id
 * @property string created_at
 * @property string updated_at
 */
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
            'price' => $this->price,
        ];
    }
}
