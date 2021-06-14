<?php
namespace App\Http\Requests\V2\VpnSession;

use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
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
            'vpn_service_id' => [
                'required',
                'string',
                Rule::exists(VpnService::class, 'id')->whereNull('deleted_at'),
            ],
            'vpn_endpoint_id' => [
                'required',
                'string',
                Rule::exists(VpnEndpoint::class, 'id')->whereNull('deleted_at'),
            ],
            'remote_ip' => '',
            'remote_networks' => '',
            'local_networks' => '',
        ];
    }
}
