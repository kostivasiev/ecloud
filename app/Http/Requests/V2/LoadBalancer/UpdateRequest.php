<?php

namespace App\Http\Requests\V2\LoadBalancer;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateLoadBalancerClusterRequest
 * @package App\Http\Requests\V2
 */
class UpdateRequest extends FormRequest
{
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
