<?php

namespace App\Http\Requests\V2\NetworkAclRulePort;

use App\Models\V2\NetworkAclRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidPortReference;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string|max:50',
            'network_acl_rule_id' => [
                'required',
                'string',
                'exists:ecloud.network_acl_rules,id,deleted_at,NULL',
                new ExistsForUser(NetworkAclRule::class),
            ],
            'protocol' => [
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'required',
                'string',
                new ValidPortReference()
            ],
            'destination' => [
                'required',
                'string',
                new ValidPortReference()
            ],
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
