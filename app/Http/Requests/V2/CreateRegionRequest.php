<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateRegionRequest
 * @package App\Http\Requests\V2
 */
class CreateRegionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255'
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
            'name.required' => 'The :attribute field is required',
        ];
    }
}
