<?php
namespace App\Http\Requests\V2\VpnSession;

use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function rules()
    {
        return [
            'name' => 'sometimes|required|string',
            'remote_ip' => 'sometimes|required|string',
            'remote_networks' => 'sometimes|required|string',
            'local_networks' => 'sometimes|required|string',
        ];
    }
}
