<?php

namespace App\Http\Requests\V2\NetworkRulePort;

use App\Models\V2\NetworkRulePort;
use App\Rules\V2\IsPortWithinExistingRange;
use App\Rules\V2\IsUniquePortOrRange;
use App\Rules\V2\ValidPortReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class Update extends FormRequest
{
    public function rules()
    {
        $networkRule = (NetworkRulePort::findOrFail(Request::route('networkRulePortId')))
            ->networkRule;
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
                new IsUniquePortOrRange(NetworkRulePort::class, $networkRule->id),
                new IsPortWithinExistingRange(NetworkRulePort::class, $networkRule->id),
            ],
            'destination' => [
                'required_if:protocol,TCP,UDP',
                'string',
                new ValidPortReference(),
                new IsUniquePortOrRange(NetworkRulePort::class, $networkRule->id),
                new IsPortWithinExistingRange(NetworkRulePort::class, $networkRule->id),
            ],
        ];
    }
}
