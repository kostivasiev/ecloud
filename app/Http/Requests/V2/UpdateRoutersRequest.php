<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class UpdateRoutersRequest extends FormRequest
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
            'name'    => 'sometimes|required|string',
            'gateway_id' => 'sometimes|required|uuid',
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
            'gateway_id.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
