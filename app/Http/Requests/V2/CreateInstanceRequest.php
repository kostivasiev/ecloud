<?php
namespace App\Http\Requests\V2;

use App\Models\V2\Network;
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
            'name'    => 'nullable|string',
            'network_id' => [
                'sometimes',
                'required_without:vpc_id',
                'nullable',
                'string',
                'exists:ecloud.networks,id',
                new ExistsForUser(Network::class)
            ],
            'vpc_id' => [
                'sometimes',
                'required_without:network_id',
                'nullable',
                'string',
                'exists:ecloud.vpcs,id',
            ]
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
            'network_id.required' => 'The :attribute field is required',
            'network_id.required_without' => 'The :attribute field is required if the vpc_id is not specified',
            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'No valid Vpc record found for specified :attribute'
        ];
    }
}
