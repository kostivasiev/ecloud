<?php

namespace App\Http\Requests\V2\LoadBalancer;

use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateLoadBalancerClusterRequest
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
        return true;
    }

    /**
     * Get the val
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'nullable|string'
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
