<?php
namespace App\Http\Requests\V2\VpnSession;

use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function rules()
    {
        return [
            'name' => 'sometimes|required|string',
            'vpn_service_id' => 'required|array|min:1',
            'vpn_service_id.*' => [
                'required',
                'string',
                Rule::exists(VpnService::class, 'id')->whereNull('deleted_at'),
                'distinct',
                new ExistsForUser(VpnService::class),
                new IsResourceAvailable(VpnService::class),
            ],
            'vpn_endpoint_id' => 'required|array|min:1',
            'vpn_endpoint_id.*' => [
                'required',
                'string',
                Rule::exists(VpnEndpoint::class, 'id')->whereNull('deleted_at'),
                'distinct',
                new ExistsForUser(VpnEndpoint::class),
                new IsResourceAvailable(VpnEndpoint::class),
            ],
            'remote_ip' => 'sometimes|required|string',
            'remote_networks' => 'sometimes|required|string',
            'local_networks' => 'sometimes|required|string',
        ];
    }
}
