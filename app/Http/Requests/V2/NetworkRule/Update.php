<?php

namespace App\Http\Requests\V2\NetworkRule;

use App\Models\V2\NetworkRulePort;
use App\Rules\V2\IsPortWithinExistingRange;
use App\Rules\V2\IsUniquePortOrRange;
use App\Rules\V2\ValidFirewallRuleSourceDestination;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Request;

class Update extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $networkRuleId = Request::route('networkRuleId');
        return [
            'name' => 'sometimes|nullable|string|max:255',
            'sequence' => [
                'sometimes',
                'required',
                'integer',
                'max:5000'
            ],
            'source' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination(),
                new IsUniquePortOrRange(NetworkRulePort::class, $networkRuleId),
                new IsPortWithinExistingRange(NetworkRulePort::class, $networkRuleId),
            ],
            'destination' => [
                'sometimes',
                'required',
                'string',
                new ValidFirewallRuleSourceDestination(),
                new IsUniquePortOrRange(NetworkRulePort::class, $networkRuleId),
                new IsPortWithinExistingRange(NetworkRulePort::class, $networkRuleId),
            ],
            'direction' => 'sometimes|required|string|in:IN,OUT,IN_OUT',
            'action' => 'sometimes|required|string|in:ALLOW,DROP,REJECT',
            'enabled' => 'sometimes|required|boolean',
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [];
    }
}
