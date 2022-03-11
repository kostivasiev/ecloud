<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateRegionRequest
 * @package App\Http\Requests\V2
 */
class UpdateRegionRequest extends FormRequest
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
        ];
    }
}
