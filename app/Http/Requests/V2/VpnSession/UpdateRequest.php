<?php
namespace App\Http\Requests\V2\VpnSession;

use App\Models\V2\VpnProfileGroup;
use App\Rules\V2\IsNotMaxCommaSeperatedItems;
use App\Rules\V2\IsSameAvailabilityZone;
use App\Rules\V2\ValidCidrNetworkCsvString;
use App\Rules\V2\ValidIpv4;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
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
            'vpn_profile_group_id' => [
                'sometimes',
                'required',
                'string',
                Rule::exists(VpnProfileGroup::class, 'id')->whereNull('deleted_at'),
                new IsSameAvailabilityZone($vpnSessionId),
            ],
            'remote_ip' => [
                'sometimes',
                'required',
                'string',
                new ValidIpv4(),
            ],
            'remote_networks' => [
                'sometimes',
                'required_without:local_networks',
                'string',
                new ValidCidrNetworkCsvString(),
                new IsNotMaxCommaSeperatedItems(config('vpn-session.max_remote_networks'))
            ],
            'local_networks' => [
                'sometimes',
                'required_without:remote_networks',
                'string',
                new ValidCidrNetworkCsvString(),
                new IsNotMaxCommaSeperatedItems(config('vpn-session.max_local_networks'))
            ],
        ];
    }

    public function messages()
    {
        return [
            'vpn_service_id.unique' => 'The :attribute is already in use for this session',
            'vpn_endpoint_id.unique' => 'The :attribute is already in use for this session',
        ];
    }
}
