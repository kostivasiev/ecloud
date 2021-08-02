<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string',
        ];
    }
}
