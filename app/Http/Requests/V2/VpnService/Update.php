<?php

namespace App\Http\Requests\V2\VpnService;

use App\Models\V2\Router;
use App\Models\V2\VpnService;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVpnsRequest
 * @package App\Http\Requests\V2
 */
class Update extends FormRequest
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
        $vpnServiceId = $this->route()[2]['vpnServiceId'];
        return [
            'name' => 'sometimes|required|string',
            'router_id' => [
                'sometimes',
                'required',
                'string',
                Rule::exists(Router::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnService::class, 'router_id')
                    ->whereNull('deleted_at')
                    ->ignore($vpnServiceId),
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
