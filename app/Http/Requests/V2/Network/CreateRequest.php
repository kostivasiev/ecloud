<?php

namespace App\Http\Requests\V2\Network;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsNotOverlappingSubnet;
use App\Rules\V2\IsPrivateSubnet;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsRestrictedSubnet;
use App\Rules\V2\IsSubnetBigEnough;
use App\Rules\V2\ValidCidrSubnet;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateNetworkRequest
 * @package App\Http\Requests\V2
 */
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
            'router_id' => [
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class),
                new IsResourceAvailable(Router::class),
            ],
            'subnet' => [
                'required',
                'string',
                'bail',
                new ValidCidrSubnet(),
                new isPrivateSubnet(),
                new isNotOverlappingSubnet(),
                new IsSubnetBigEnough(),
                new IsRestrictedSubnet(),
            ]
        ];
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            'router_id.required' => 'The :attribute field is required',
            'subnet.unique' => 'The :attribute is already assigned to another network',
        ];
    }
}
