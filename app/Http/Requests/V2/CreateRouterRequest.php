<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Router;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\RouterThroughput\ExistsForAvailabilityZone;
use UKFast\FormRequests\FormRequest;

class CreateRouterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ($this->user()->isAdmin());
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
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'router_throughput_id' => [
                'sometimes',
                'required',
                'exists:ecloud.router_throughputs,id,deleted_at,NULL',
                new ExistsForAvailabilityZone($this->request->get('vpc_id'))
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
            'vpc_id.required' => 'The :attribute field is required',
            'vpc_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
