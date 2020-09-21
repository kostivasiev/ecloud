<?php
namespace App\Http\Requests\V2\Instance;

use App\Models\V2\FloatingIp;
use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class DeployRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'network_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
            ],
            'floating_ip_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                new ExistsForUser(FloatingIp::class),
            ],
            'appliance_data' => [
                'sometimes',
                'required',
                'string',
            ],
            'volume_capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:' . config('volume.capacity.min'),
                'max:' . config('volume.capacity.max'),
            ],
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'network_id.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.exists' => 'The specified :attribute was not found',
            'floating_ip_id.required' => 'The :attribute field, when specified, cannot be null',
            'floating_ip_id.exists' => 'The specified :attribute was not found',
            'appliance_data.required' => 'The :attribute field, when specified, cannot be null',
            'volume_capacity.required' => 'The :attribute field, when specified, cannot be null',
            'volume_capacity.min' => 'specified :attribute is below the minimum of ' . config('volume.capacity.min'),
            'volume_capacity.max' => 'specified :attribute is above the maximum of ' . config('volume.capacity.max'),
        ];
    }
}
