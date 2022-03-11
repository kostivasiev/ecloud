<?php

namespace App\Http\Requests\V2\Nic;

use App\Models\V2\IpAddress;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IpAddress\IsClusterType;
use App\Rules\V2\IpAddress\IsSameNetworkAsNic;
use Illuminate\Foundation\Http\FormRequest;

class AssociateIpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
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
