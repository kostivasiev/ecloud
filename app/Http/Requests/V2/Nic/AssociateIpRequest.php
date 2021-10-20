<?php

namespace App\Http\Requests\V2\Nic;

use App\Models\V2\IpAddress;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IpAddress\IsClusterType;
use App\Rules\V2\IpAddress\IsSameNetworkAsNic;
use UKFast\FormRequests\FormRequest;

class AssociateIpRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    protected function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'ip_address_id' => [
                'required',
                'string',
                new ExistsForUser(IpAddress::class),
                new IsClusterType,
                new IsSameNetworkAsNic(app('request')->route('nicId'))
            ],
        ];
    }
}
