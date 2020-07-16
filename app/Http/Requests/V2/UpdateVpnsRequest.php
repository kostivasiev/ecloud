<?php

namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVpnsRequest
 * @package App\Http\Requests\V2
 */
class UpdateVpnsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'router_id'            => 'sometimes|required|string|exists:ecloud.router,id,deleted_at,NULL',
            'availability_zone_id' => 'sometimes|required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
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
            'router_id.required'            => 'The :attribute field, when specified, cannot be null',
            'availability_zone_id.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
