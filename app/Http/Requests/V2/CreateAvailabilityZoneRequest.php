<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class CreateAvailabilityZoneRequest extends FormRequest
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
            'code'    => 'required|string',
            'name'    => 'required|string',
            'datacentre_site_id' => 'required|integer',
            'region_id' => 'required|string|exists:ecloud.regions,id,deleted_at,NULL',
            'is_public' => 'sometimes|required|boolean',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'The :attribute field is required',
            'name.required' => 'The :attribute field is required',
            'datacentre_site_id.required' => 'The :attribute field is required',
            'region_id.required' => 'The :attribute field is required',
            'region_id.exists' => 'The specified :attribute was not found'
        ];
    }
}
