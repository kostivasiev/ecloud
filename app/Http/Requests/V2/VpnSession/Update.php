<?php
namespace App\Http\Requests\V2\VpnSession;

use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnEndpointVpnSession;
use App\Models\V2\VpnService;
use App\Models\V2\VpnServiceVpnSession;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\ValidCidrNetworkCsvString;
use App\Rules\V2\ValidIpv4;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function rules()
    {
        $vpnSessionId = $this->route()[2]['vpnSessionId'];
        return [
            'name' => 'sometimes|required|string',
            'vpn_service_id' => 'sometimes|required|array|min:1',
            'vpn_service_id.*' => [
                'sometimes',
                'required',
                'string',
                Rule::exists(VpnService::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnServiceVpnSession::class, 'vpn_service_id')
                    ->where('vpn_session_id', $vpnSessionId),
                'distinct',
                new ExistsForUser(VpnService::class),
                new IsResourceAvailable(VpnService::class),
            ],
            'vpn_endpoint_id' => 'sometimes|required|array|min:1',
            'vpn_endpoint_id.*' => [
                'sometimes',
                'required',
                'string',
                Rule::exists(VpnEndpoint::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnEndpointVpnSession::class, 'vpn_endpoint_id')
                    ->where('vpn_session_id', $vpnSessionId),
                'distinct',
                new ExistsForUser(VpnEndpoint::class),
                new IsResourceAvailable(VpnEndpoint::class),
            ],
            'remote_ip' => [
                'sometimes',
                'required',
                'string',
                new ValidIpv4(),
            ],
            'remote_networks' => [
                'sometimes',
                'required',
                'string',
                new ValidCidrNetworkCsvString()
            ],
            'local_networks' => [
                'sometimes',
                'required',
                'string',
                new ValidCidrNetworkCsvString()
            ],
        ];
    }

    public function messages()
    {
        return [
            'vpn_service_id.*.unique' => 'The :attribute is already in use for this session',
            'vpn_endpoint_id.*.unique' => 'The :attribute is already in use for this session',
        ];
    }

}
