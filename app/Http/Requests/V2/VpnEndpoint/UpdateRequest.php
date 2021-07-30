<?php
namespace App\Http\Requests\V2\VpnEndpoint;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    public function rules()
    {
        $id = $this->route()[2]['vpnEndpointId'];
        return [
            'name' => 'sometimes|required|string',
        ];
    }
}
