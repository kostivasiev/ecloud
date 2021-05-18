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
            'reseller_id' => $this->reseller_id,
            'logo_uri' => $this->logo_uri,
            'documentation_uri' => $this->documentation_uri,
            'description' => $this->description,
            'platform' => $this->platform,
            'public' => $this->public,
            'sync' => $this->sync,
        ];

        $tz = new \DateTimeZone(config('app.timezone'));

        if (!empty($this->vpc_id) && !$request->user()->isAdmin()) {
            // show the timestamps for private images
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        if ($request->user()->isAdmin()) {
            $data['script_template'] = $this->script_template;
            $data['vm_template'] = $this->vm_template;
            $data['active'] = $this->active;
        }

        return $data;
    }
}
