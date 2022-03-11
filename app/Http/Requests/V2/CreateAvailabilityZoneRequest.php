<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => 'required|string',
            'name' => 'required|string|max:255',
            'datacentre_site_id' => 'required|integer',
            'ucs_compute_name' => 'sometimes|required|string',
            'region_id' => 'required|string|exists:ecloud.regions,id,deleted_at,NULL',
            'is_public' => 'sometimes|required|boolean',
            'san_name' => [
                'sometimes',
                'required',
                'string'
            ]
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
