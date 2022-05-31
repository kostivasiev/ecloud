<?php

namespace App\Http\Requests\V2\FloatingIp;

use App\Models\V2\FloatingIp;
use App\Models\V2\IpAddress;
use App\Models\V2\Nic;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsSameAvailabilityZone;
use Illuminate\Foundation\Http\FormRequest;

class AssignRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'resource_id' =>
                [
                    'required',
                    'string',
                    new ExistsForUser([
                        /** @deprecated - we need to remove assigning NICs to a fip **/
                        Nic::class,
                        FloatingIp::class,
                        IpAddress::class
                    ]),
                    new IsSameAvailabilityZone(app('request')->route('fipId'))
                ],
        ];
    }

    // Add fipId route parameter to validation data
    public function all($keys = null)
    {
        return array_merge(
            parent::all(),
            [
                'id' => app('request')->route('fipId')
            ]
        );
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'resource_id.required' => 'The :attribute field is required',
            'id.unique' => 'The floating IP is already assigned'
        ];
    }
}
