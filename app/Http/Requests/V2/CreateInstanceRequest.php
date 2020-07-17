<?php
namespace App\Http\Requests\V2;

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
            'network_id' => ['required', 'string', 'exists:ecloud.networks,id'],
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
        ];
    }
}
