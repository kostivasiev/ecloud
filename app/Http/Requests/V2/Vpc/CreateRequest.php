<?php

namespace App\Http\Requests\V2\Vpc;

use UKFast\FormRequests\FormRequest;

/**
 * Class CreateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
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
            'region_id' => 'required|string|exists:ecloud.regions,id,deleted_at,NULL'
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
            'region_id.required' => 'The :attribute field is required',
            'region_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
