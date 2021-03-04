<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class ImageParameterResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string created_at
 * @property string updated_at
 */
class ImageParameterResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'name' => $this->name,
            'key' => $this->key,
            'type' => $this->type,
            'description' => $this->description,
            'required' => $this->required,
            'validation_rule' => $this->validation_rule,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
