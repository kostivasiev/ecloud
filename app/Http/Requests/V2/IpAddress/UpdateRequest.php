<?php

namespace App\Http\Requests\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Rules\V2\IpAddress\IsAvailable;
use App\Rules\V2\IpAddress\IsInSubnet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $ipAddress = IpAddress::forUser(Auth::user())
            ->findOrFail(app('request')
                ->route('ipAddressId'));
        $rules = [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255'
            ],
            'type' => [
                'sometimes',
                'required',
                'string',
                Rule::in([IpAddress::TYPE_NORMAL,IpAddress::TYPE_CLUSTER])
            ]
        ];

        if (Auth::user()->isAdmin()) {
            $rules['ip_address'] = [
                'sometimes',
                'required',
                'ip',
                new IsInSubnet($ipAddress->network->id),
                'bail',
                new IsAvailable($ipAddress->network->id),
            ];
        }
        return $rules;
    }
}
