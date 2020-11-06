<?php

namespace App\Http\Requests\V2\FirewallRulePort;

use App\Models\V2\FirewallRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidIpFormatCsvString;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'nullable',
                'string'
            ],
            'firewall_rule_id' => [
                'sometimes',
                'string',
                'exists:ecloud.firewall_rules,id,deleted_at,NULL',
                new ExistsForUser(FirewallRule::class)
            ],
            'protocol' => [
                'sometimes',
                'required',
                'string',
                'in:TCP,UDP'
            ],
            'source' => [
                'sometimes',
                'nullable',
                'string',
                new ValidIpFormatCsvString()
            ],
            'destination' => [
                'sometimes',
                'nullable',
                'string',
                new ValidIpFormatCsvString()
            ]
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
            'exists' => 'The specified :attribute was not found',
            'protocol.in' => 'The :attribute field must contain one of TCP or UDP',
        ];
    }
}
