<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use App\Models\V2\FloatingIp;
use App\Models\V2\VpnEndpoint;
use App\Models\V2\VpnService;
use App\Models\V2\VpnServiceVpnEndpoint;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string',
            'vpn_service_id' => [
                'required',
                Rule::exists(VpnService::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(VpnService::class),
                new IsResourceAvailable(VpnService::class),
            ],
            'floating_ip_id' => [
                'sometimes',
                'required',
                Rule::exists(FloatingIp::class, 'id')->where(function ($query) {
                    $query->whereNull('resource_id');
                    $query->whereNull('deleted_at');
                }),
                new ExistsForUser(FloatingIp::class),
                new IsResourceAvailable(FloatingIp::class),
            ],
        ];
    }

    public function messages()
    {
        return [
            'unique' => 'A vpn endpoint already exists for the specified :attribute',
            'floating_ip_id.exists' => 'The :attribute is already assigned to a resource',
        ];
    }
}
