<?php

namespace App\Http\Requests\V2\IpAddress;

use App\Models\V2\IpAddress;
use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IpAddress\IsInSubnet;
use App\Rules\V2\IpAddress\IsAvailable;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:255',
            'ip_address' => [
                'required',
                'ip',
                new IsInSubnet(app('request')->input('network_id')),
                'bail',
                new IsAvailable(app('request')->input('network_id')),
            ],
            'network_id' => [
                'required',
                'string',
                Rule::exists(Network::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(Network::class),
            ],
        ];
    }
}
