<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string',
        ];
    }
}
