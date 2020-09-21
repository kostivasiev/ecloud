<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class CreateInstanceRequest extends FormRequest
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
            'name'         => 'nullable|string',
            'vpc_id'       => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id',
                new ExistsForUser(Vpc::class)
            ],
            'appliance_id' => [
                'required',
                'uuid',
                'exists:ecloud.appliance,appliance_uuid'
            ],
            'vcpu_tier'    => 'required|string', # needs exists: adding once tier has been added to db
            'vcpu_cores'   => [
                'required',
                'numeric',
                'min:'.config('cpu.cores.min'),
                'max:'.config('cpu.cores.max'),
            ],
            'ram_capacity' => [
                'required',
                'numeric',
                'min:'.config('ram.capacity.min'),
                'max:'.config('ram.capacity.max'),
            ],
            'locked'       => 'sometimes|required|boolean',
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
            'vpc_id.required'       => 'The :attribute field is required',
            'vpc_id.exists'         => 'No valid Vpc record found for specified :attribute',
            'appliance_id.required' => 'The :attribute field is required',
            'appliance_id.exists'   => 'The :attribute is not a valid Appliance',
            'vcpu_tier.required'    => 'The :attribute field is required',
            'vcpu_cores.required'   => 'The :attribute field is required',
            'vcpu_cores.min'        => 'Specified :attribute is below the minimum of '.config('cpu.cores.min'),
            'vcpu_cores.max'        => 'Specified :attribute is above the maximum of '.config('cpu.cores.max'),
            'ram_capacity.required' => 'The :attribute field is required',
            'ram_capacity.min'      => 'Specified :attribute is below the minimum of '.config('ram.capacity.min'),
            'ram_capacity.max'      => 'Specified :attribute is above the maximum of '.config('ram.capacity.max'),
        ];
    }
}
