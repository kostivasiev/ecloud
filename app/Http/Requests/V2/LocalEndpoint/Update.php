<?php
namespace App\Http\Requests\V2\LocalEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\Vpn;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        $id = $this->route()[2]['localEndpointId'];
        return [
            'name' => 'sometimes|required|string',
            'vpn_id' => [
                'sometimes',
                'required',
                'exists:ecloud.vpns,id,deleted_at,NULL',
                Rule::unique('ecloud.local_endpoints', 'vpn_id')
                    ->ignore($id, 'id'),
                new ExistsForUser(Vpn::class),
                new IsResourceAvailable(Vpn::class),
            ],
            'fip_id' => [
                'sometimes',
                'required',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                Rule::unique('ecloud.local_endpoints', 'fip_id')
                    ->ignore($id, 'id'),
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
