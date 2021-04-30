<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VirtualPrivateCloudResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string reseller_id
 * @property string region_id
 * @property bool support_enabled
 * @property bool console_enabled
 * @property bool advanced_networking
 * @property string created_at
 * @property string updated_at
 */
class VpcResource extends UKFastResource
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
            'region_id' => $this->region_id,
            'sync' => $this->sync,
            'task' => $this->task,
            'support_enabled' => $this->support_enabled,
            'console_enabled' => $this->console_enabled,
            'advanced_networking' => $this->advanced_networking,
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
            $data['reseller_id'] = $this->reseller_id;
        }

        return $data;
    }
}
