<?php
namespace App\Http\Requests\V2\VpnSession;

use App\Rules\V2\IsValidSshPublicKey;
use Illuminate\Foundation\Http\FormRequest;

class UpdateKeyRequest extends FormRequest
{
    public function rules()
    {
        return [
            'psk' => [
                'required',
                'string',
                new IsValidSshPublicKey(),
            ],
        ];
    }
}
