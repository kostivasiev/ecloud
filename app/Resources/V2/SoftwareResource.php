<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class SoftwareResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'platform' => $this->platform,
            'visibility' => $this->visibility,
        ];

        if ($request->user()->isAdmin()) {
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
