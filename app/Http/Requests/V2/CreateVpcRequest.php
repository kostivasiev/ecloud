<?php

namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

/**
 * Class CreateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2
 */
class CreateVpcRequest extends FormRequest
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
            'name' => 'required|string',
            'region_id' => 'required|string'
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
            'name.required' => 'The :attribute field is required',
            'region_id.required' => 'The :attribute field is required',
        ];
    }
}
