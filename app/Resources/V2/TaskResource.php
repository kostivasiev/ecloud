<?php

namespace App\Resources\V2;

use App\Services\V2\KingpinService;
use DateTimeZone;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;
use Illuminate\Support\Facades\Log;

class TaskResource extends UKFastResource
{
    /**
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
