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
            'documentation_uri' => $this->documentation_uri ?: null,
            'description' => $this->description ?: null,
            'platform' => $this->platform,
            'visibility' => $this->visibility,
        ];

        if ($request->user()->isAdmin()) {
            $data['active'] = $this->active;
            $data['public'] = $this->public;
            $data['license_id'] = $this->license_id;
            $data['reseller_id'] = $this->reseller_id;
            $data['script_template'] = $this->script_template;
            $data['vm_template'] = $this->vm_template;
        }

        $tz = new \DateTimeZone(config('app.timezone'));

        if ($request->user()->isAdmin() || $this->isOwner()) {
            $data['created_at'] = $this->created_at === null ? null : Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = $this->updated_at === null ? null : Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        if ($this->isOwner()) {
            $data['sync'] = $this->sync;
        }

        return $data;
    }
}
