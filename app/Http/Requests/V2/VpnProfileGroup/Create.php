<?php
namespace App\Http\Requests\V2\VpnProfileGroup;

use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|max:255',
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
            ],
            'ike_profile_id' => 'required|string',
            'ipsec_profile_id' => 'required|string'
        ];
    }
}
