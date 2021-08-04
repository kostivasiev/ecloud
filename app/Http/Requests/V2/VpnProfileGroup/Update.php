<?php
namespace App\Http\Requests\V2\VpnProfileGroup;

use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|max:255',
            'ike_profile_id' => 'sometimes|required|string',
            'ipsec_profile_id' => 'sometimes|required|string',
            'dpd_profile_id' => 'sometimes|required|string',
        ];
    }
}
