<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class SSHKeyPairResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string reseller_id
 * @property string name
 * @property string public_key
 * @property string created_at
 * @property string updated_at
 */
class SshKeyPairResource extends UKFastResource
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
            'public_key' => $this->public_key,
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
