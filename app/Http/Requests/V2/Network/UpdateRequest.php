<?php

namespace App\Http\Requests\V2\Network;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsNotOverlappingSubnet;
use App\Rules\V2\IsPrivateSubnet;
use App\Rules\V2\IsRestrictedSubnet;
use App\Rules\V2\IsSubnetBigEnough;
use App\Rules\V2\ValidCidrSubnet;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateNetworksRequest
 * @package App\Http\Requests\V2
 */
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
        $networkId = app()->make('request')->route('networkId');
        return [
            'name' => 'sometimes|required|string|max:255',
            'subnet' => [
                'sometimes',
                'string',
                new ValidCidrSubnet(),
                new isPrivateSubnet(),
                new isNotOverlappingSubnet($networkId),
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'subnet.unique' => 'The :attribute is already assigned to another network',
        ];
    }
}
