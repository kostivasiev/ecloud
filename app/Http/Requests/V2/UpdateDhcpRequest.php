<?php

namespace App\Http\Requests\V2;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateDhcpsRequest
 * @package App\Http\Requests\V2
 */
class UpdateDhcpRequest extends FormRequest
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
}
