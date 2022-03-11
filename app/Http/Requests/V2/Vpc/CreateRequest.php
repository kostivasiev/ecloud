<?php

namespace App\Http\Requests\V2\Vpc;

use App\Rules\V2\PaymentRequired;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'region_id' => [
                'required',
                'string',
                'exists:ecloud.regions,id,deleted_at,NULL'
            ],
            'console_enabled' => 'sometimes|boolean',
            'advanced_networking' => 'sometimes|boolean',
            'support_enabled' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
