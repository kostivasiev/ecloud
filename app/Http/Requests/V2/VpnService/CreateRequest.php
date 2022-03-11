<?php

namespace App\Http\Requests\V2\VpnService;

use App\Models\V2\Router;
use App\Models\V2\VpnService;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class Create
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
                Rule::exists(Router::class, 'id')->whereNull('deleted_at'),
                Rule::unique(VpnService::class, 'router_id')->whereNull('deleted_at'),
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
