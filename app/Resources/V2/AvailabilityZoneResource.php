<?php
namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class AvailabilityZonesResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string code
 * @property string name
 * @property int site_id
 * @property string created_at
 * @property string updated_at
 */
class AvailabilityZoneResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id'         => $this->id,
            'code'       => $this->code,
            'name'       => $this->name,
            'datacentre_site_id' => $this->datacentre_site_id,
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];

        if ($request->user->isAdministrator) {
            $data['is_public'] = $this->is_public;
        }

        return $data;
    }
}
