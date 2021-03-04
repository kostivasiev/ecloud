<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class ImageResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string created_at
 * @property string updated_at
 */
class ImageResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'logo_uri' => $this->logo_uri,
            'documentation_uri' => $this->documentation_uri,
            'description' => $this->description,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];


        if ($request->user()->isAdmin()) {
            $data['is_public'] = $this->is_public;
            $data['active'] = $this->active;
        }

        return $data;
    }
}
