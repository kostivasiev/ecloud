<?php
namespace App\Http\Requests\V2\NetworkPolicy;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
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
        ];
    }

    public function messages()
    {
        return [];
    }
}
