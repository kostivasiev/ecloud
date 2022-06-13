<?php

namespace App\Http\Requests\V2\NetworkRulePort;

use App\Models\V2\NetworkRulePort;
use App\Rules\V2\FirewallRulePort\UniquePortListRule;
use App\Rules\V2\FirewallRulePort\UniquePortRule;
use App\Rules\V2\ValidPortReference;
use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
    public function rules()
    {
        return [
            'name' => 'sometimes|nullable|string|max:255',
            'protocol' => [
                'sometimes',
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidPortReference(),
                new UniquePortRule(NetworkRulePort::class),
                new UniquePortListRule(NetworkRulePort::class),
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidPortReference(),
                new UniquePortRule(NetworkRulePort::class),
                new UniquePortListRule(NetworkRulePort::class),
            ],
        ];
    }
}
