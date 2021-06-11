<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\Vpn;
use App\Models\V2\VpnEndpoint;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        $id = $this->route()[2]['vpnEndpointId'];
        return [
            'name' => 'sometimes|required|string',
            'vpn_id' => [
                'sometimes',
                'required',
                Rule::exists(Vpn::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnEndpoint::class, 'vpn_id')
                    ->ignore($id, 'id'),
                new ExistsForUser(Vpn::class),
                new IsResourceAvailable(Vpn::class),
            ],
            'fip_id' => [
                'sometimes',
                'required',
                Rule::exists(FloatingIp::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnEndpoint::class, 'fip_id')
                    ->ignore($id, 'id'),
                new ExistsForUser(FloatingIp::class),
                new IsResourceAvailable(FloatingIp::class),
            ],
        ];
    }

    public function messages()
    {
        return [
            'unique' => 'A vpn endpoint already exists for the specified :attribute',
        ];
    }
}
