<?php

namespace App\Http\Requests\V2\Vpc;

use App\Rules\V2\PaymentRequired;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class UpdateRequest extends FormRequest
{
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
            'support_enabled' => [
                'sometimes',
                'boolean',
            ],
        ];
    }
}
