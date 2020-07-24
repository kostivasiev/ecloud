<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class UpdateAvailabilityZoneRequest extends FormRequest
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
            'code'    => 'sometimes|required|string',
            'name'    => 'sometimes|required|string',
            'datacentre_site_id' => 'sometimes|required|integer',
            'is_public' => 'sometimes|required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'The :attribute field, when specified, cannot be null',
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'datacentre_site_id.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
