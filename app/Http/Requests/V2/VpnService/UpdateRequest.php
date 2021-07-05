<?php

namespace App\Http\Requests\V2\VpnService;

use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVpnsRequest
 * @package App\Http\Requests\V2
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
            'name' => 'sometimes|required|string',
        ];
    }
}
