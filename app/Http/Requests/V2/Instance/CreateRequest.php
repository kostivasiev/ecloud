<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\FloatingIp;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string',
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'appliance_id' => [
                'required',
                'uuid',
                'exists:ecloud.appliance,appliance_uuid'
            ],
            'vcpu_cores' => [
                'required',
                'numeric',
                'min:' . config('instance.cpu_cores.min'),
                'max:' . config('instance.cpu_cores.max'),
            ],
            'ram_capacity' => [
                'required',
                'numeric',
                'min:' . config('instance.ram_capacity.min'),
                'max:' . config('instance.ram_capacity.max'),
            ],
            'availability_zone_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL'
            ],
            'locked' => 'sometimes|required|boolean',
            'platform' => 'sometimes|required|in:Windows,Linux',
            'backup_enabled' => 'sometimes|required|boolean',
            'network_id' => [
                'sometimes',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
            ],
            'floating_ip_id' => [
                'sometimes',
                'string',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                'required_without:requires_floating_ip',
                new ExistsForUser(FloatingIp::class),
            ],
            'requires_floating_ip' => [
                'sometimes',
                'required_without:floating_ip_id',
                'boolean',
            ],
            'appliance_data' => [
                'sometimes',
                'required',
                'string',
            ],
            'user_script' => [
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
            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'No valid Vpc record found for specified :attribute',
            'appliance_id.required' => 'The :attribute field is required',
            'appliance_id.exists' => 'The :attribute is not a valid Appliance',
            'vcpu_tier.required' => 'The :attribute field is required',
            'vcpu_cores.required' => 'The :attribute field is required',
            'vcpu_cores.min' => 'Specified :attribute is below the minimum of '
                . config('instance.cpu_cores.min'),
            'vcpu_cores.max' => 'Specified :attribute is above the maximum of '
                . config('instance.cpu_cores.max'),
            'ram_capacity.required' => 'The :attribute field is required',
            'ram_capacity.min' => 'Specified :attribute is below the minimum of '
                . config('instance.ram_capacity.min'),
            'ram_capacity.max' => 'Specified :attribute is above the maximum of '
                . config('instance.ram_capacity.max'),
            'availability_zone_id.exists' => 'No valid Availability Zone exists for :attribute',
            'network_id.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.exists' => 'The specified :attribute was not found',
            'floating_ip_id.required' => 'The :attribute field, when specified, cannot be null',
            'floating_ip_id.exists' => 'The specified :attribute was not found',
            'appliance_data.required' => 'The :attribute field, when specified, cannot be null',
            'user_script.required' => 'The :attribute field, when specified, cannot be null',
            'volume_capacity.required' => 'The :attribute field, when specified, cannot be null',
            'volume_capacity.min' => 'specified :attribute is below the minimum of ' . config('volume.capacity.min'),
            'volume_capacity.max' => 'specified :attribute is above the maximum of ' . config('volume.capacity.max'),
        ];
    }
}
