<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\ValidMacAddress;
use UKFast\FormRequests\FormRequest;

class CreateNicRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'mac_address' => [
                'required',
                'string',
                new ValidMacAddress()
            ],
            'instance_id' => [
                'required',
                'string',
                'exists:ecloud.instances,id,deleted_at,NULL',
                new IsResourceAvailable(Instance::class),
            ],
            'network_id' => [
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new IsResourceAvailable(Network::class),
            ],
        ];
    }

    public function messages()
    {
        return [
            'mac_address.required' => 'The :attribute field is required',
            'mac_address.string' => 'The :attribute field must be a string',
            'instance_id.required' => 'The :attribute field is required',
            'instance_id.string' => 'The :attribute field must be a string',
            'instance_id.exists' => 'The :attribute is not a valid Instance',
            'network_id.required' => 'The :attribute field is required',
            'network_id.string' => 'The :attribute field must be a string',
            'network_id.exists' => 'The :attribute is not a valid Network',
        ];
    }
}
