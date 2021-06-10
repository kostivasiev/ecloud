<?php
namespace App\Http\Requests\V2\LocalEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\Vpn;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
            'vpn_id' => [
                'required',
                'exists:ecloud.vpns,id,deleted_at,NULL',
                'unique:ecloud.local_endpoints,vpn_id,deleted_at,NULL',
                new ExistsForUser(Vpn::class),
                new IsResourceAvailable(Vpn::class),
            ],
            'fip_id' => [
                'sometimes',
                'required',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                'unique:ecloud.local_endpoints,fip_id,deleted_at,NULL',
                new ExistsForUser(FloatingIp::class),
                new IsResourceAvailable(FloatingIp::class),
            ],
        ];
    }

    public function messages()
    {
        return [
            'unique' => 'A local endpoint already exists for the specified :attribute',
        ];
    }
}
