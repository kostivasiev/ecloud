<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class ImageMetadataResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string created_at
 * @property string updated_at
 */
class ImageMetadataResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->image,
            'image_id' => $this->image_id,
            'key' => $this->key,
            'value' => $this->value,
        ];

        if ($request->user()->isAdmin()) {
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
