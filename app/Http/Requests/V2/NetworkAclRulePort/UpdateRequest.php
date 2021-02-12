<?php

namespace App\Http\Requests\V2\NetworkAclRulePort;

use App\Models\V2\NetworkAclRule;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidPortReference;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
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
            'name' => 'sometimes|nullable|string|max:50',
            'network_acl_rule_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.network_acl_rules,id,deleted_at,NULL',
                new ExistsForUser(NetworkAclRule::class),
            ],
            'protocol' => [
                'sometimes',
                'required',
                'string',
                'in:TCP,UDP,ICMPv4'
            ],
            'source' => [
                'sometimes',
                'required',
                'string',
                new ValidPortReference()
            ],
            'destination' => [
                'sometimes',
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
