<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

class ScriptResource extends UKFastResource
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
            'software_id' => $this->software_id,
            'sequence' => $this->sequence,
            'script' => $this->script,
        ];

        if ($request->user()->isAdmin()) {
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
