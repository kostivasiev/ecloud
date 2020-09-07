<?php

namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2
 */
class UpdateVpcRequest extends FormRequest
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
            'name'    => 'sometimes|required|string',
            'reseller_id' => 'sometimes|required|integer',
            'region_id' => 'sometimes|required|string|exists:ecloud.regions,id,deleted_at,NULL'
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'reseller_id.required' => 'The :attribute field, when specified, cannot be null',
            'region_id.required' => 'The :attribute field, when specified, cannot be null',
            'region_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
