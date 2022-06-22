<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

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
            'code' => 'sometimes|required|string',
            'name' => 'sometimes|required|string|max:255',
            'datacentre_site_id' => 'sometimes|required|integer',
            'ucs_compute_name' => 'sometimes|required|string',
            'is_public' => 'sometimes|required|boolean',
            'region_id' => 'sometimes|required|string|exists:ecloud.regions,id,deleted_at,NULL',
            'san_name' => [
                'sometimes',
                'required',
                'string'
            ],
            'default_resource_tier_id' => [
                'sometimes',
                'required',
                'string'
                // TODO: add in an exists rule for resource tier when the CRUD has been deployed
                //Rule::exists(ResourceTier::class, 'id')->whereNull('deleted_at')
            ],
        ];
    }
}
