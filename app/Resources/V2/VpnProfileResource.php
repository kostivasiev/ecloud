<?php

namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class VpnProfileResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string ike_version
 * @property array encryption_algorithm
 * @property string digest_algorithm
 * @property string diffieHellman
 * @property string created_at
 * @property string updated_at
 */
class VpnProfileResource extends UKFastResource
{
    /**
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'ike_version' => $this->ike_version,
            'encryption_algorithm' => $this->encryption_algorithm,
            'digest_algorithm' => $this->digest_algorithm,
            'diffie_-_hellman' => $this->diffieHellman,
            'created_at' => $this->created_at === null ? null : Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => $this->updated_at === null ? null : Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
