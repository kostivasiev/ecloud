<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class CreateGatewayRequest extends FormRequest
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
            'name'    => 'required|string',
            'availability_zone_id'    => 'required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
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
            'availability_zone_id.required' => 'The :attribute field is required',
            'availability_zone_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
