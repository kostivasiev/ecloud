<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class CreateRouterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ($this->user()->isAdmin());
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
            'vpc_id' => 'required|string|exists:ecloud.virtual_private_clouds,id,deleted_at,NULL',
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
            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
