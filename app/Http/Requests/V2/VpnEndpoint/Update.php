<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpointVpnService;
use App\Models\V2\VpnService;
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
            'vpn_service_id' => [
                'sometimes',
                'required',
                Rule::exists(VpnService::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnEndpointVpnService::class, 'vpn_service_id')
                    ->ignore($id, 'vpn_endpoint_id'),
                new ExistsForUser(VpnService::class),
                new IsResourceAvailable(VpnService::class),
            ],
            'floating_ip_id' => [
                'sometimes',
                'required',
                Rule::exists(FloatingIp::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnEndpoint::class, 'floating_ip_id')
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
