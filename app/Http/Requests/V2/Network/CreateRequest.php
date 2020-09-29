<?php

namespace App\Http\Requests\V2\Network;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\ValidCidrSubnetRange;
use UKFast\FormRequests\FormRequest;

/**
 * Class CreateNetworkRequest
 * @package App\Http\Requests\V2
 */
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
            'name' => 'nullable|string',
            'router_id' => [
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                new ExistsForUser(Router::class)
            ],
            'subnet_range' => [
                'sometimes', 'nullable', 'string', new ValidCidrSubnetRange()
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
        ];
    }
}
