<?php
namespace App\Http\Requests\V2\VpnProfileGroup;

use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function rules()
    {
        return [
            'name' => 'required|string',
            'description' => 'nullable|max:255',
            'ike_profile_id' => 'required|string',
            'ipsec_profile_id' => 'required|string',
            'dpd_profile_id' => 'required|string',
        ];
    }
}
