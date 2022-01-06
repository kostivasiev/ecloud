<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class ImageParameterResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'image_id' => $this->image_id,
            'name' => $this->name,
            'key' => $this->key,
            'type' => $this->type,
            'description' => $this->description,
            'required' => $this->required,
            'validation_rule' => $this->validation_rule,
        ];

        if ($request->user()->isAdmin()) {
            $data['is_hidden'] = $this->is_hidden;
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
