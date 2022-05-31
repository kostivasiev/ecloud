<?php
namespace App\Http\Requests\V2\VpnSession;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdateKeyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'psk' => [
                'required',
                'string',
                Password::min(8)->mixedCase()->letters()->numbers()->symbols(),
            ],
        ];
    }
}
