<?php

namespace App\Http\Requests\V2\Vpc;

use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class UpdateRequest extends FormRequest
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
            'name' => 'sometimes|required|string|max:255',
            'reseller_id' => 'sometimes|required|integer',
            'console_enabled' => 'sometimes|boolean',
            'support_enabled' => 'sometimes|boolean',
        ];
    }
}
