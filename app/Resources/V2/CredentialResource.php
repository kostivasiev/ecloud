<?php
namespace App\Resources\V2;

use Illuminate\Support\Carbon;
use UKFast\Responses\UKFastResource;

/**
 * Class CredentialResource
 * @package App\Http\Resources\V2
 * @property string id
 * @property string name
 * @property string resource_id
 * @property string host
 * @property string user
 * @property string password
 * @property string port
 * @property string created_at
 * @property string updated_at
 */
class CredentialResource extends UKFastResource
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
            'resource_id' => $this->resource_id,
            'host' => $this->host,
            'user' => $this->user,
            'password' => $this->password,
            'port' => $this->port,
            'created_at' => Carbon::parse(
                $this->created_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
            'updated_at' => Carbon::parse(
                $this->updated_at,
                new \DateTimeZone(config('app.timezone'))
            )->toIso8601String(),
        ];
    }
}
