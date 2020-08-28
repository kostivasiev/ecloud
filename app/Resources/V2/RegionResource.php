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
 * @property string created_at
 * @property string updated_at
 */
class RegionResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'id'         => $this->id,
            'name'       => $this->name
        ];

        if ($request->user->isAdministrator) {
            $tz = new \DateTimeZone(config('app.timezone'));
            $data['created_at'] = Carbon::parse($this->created_at, $tz)->toIso8601String();
            $data['updated_at'] = Carbon::parse($this->updated_at, $tz)->toIso8601String();
        }

        return $data;
    }
}
