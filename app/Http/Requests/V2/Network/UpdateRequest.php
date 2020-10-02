<?php

namespace App\Http\Requests\V2\Network;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
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
        return [
            'name'    => 'sometimes|required|string',
            'router_id'    => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class)
            ],
            'subnet' => [
                'sometimes', 'nullable', 'string', new ValidCidrSubnet()
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
            'router_id.required' => 'The :attribute field, when specified, cannot be null',
            'router_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
