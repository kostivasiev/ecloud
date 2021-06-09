<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

/**
 * Class CreateVpnsRequest
 * @package App\Http\Requests\V2
 */
class CreateVpnRequest extends FormRequest
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
            'name' => 'required|string',
            'router_id' => [
                'required',
                'string',
                'exists:ecloud.routers,id,deleted_at,NULL',
                'unique:ecloud.vpns,router_id,deleted_at,NULL',
                new ExistsForUser(Router::class),
                new IsResourceAvailable(Router::class),
            ],
        ];
    }

    public function messages()
    {
        return [
            'router_id.unique' => 'A VPN already exists for the specified :attribute.',
        ];
    }
}
