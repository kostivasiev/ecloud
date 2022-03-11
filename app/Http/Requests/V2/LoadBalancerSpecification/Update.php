<?php

namespace App\Http\Requests\V2\LoadBalancerSpecification;

use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
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
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'unique:ecloud.load_balancer_specifications,id',
            ],
            'description' => [
                'string'
            ],
            'node_count' => [
                'sometimes',
                'required',
                'numeric',
                'min:1'
            ],
            'cpu' => [
                'sometimes',
                'required',
                'numeric',
                'min:1'
            ],
            'ram' => [
                'sometimes',
                'required',
                'numeric',
                'min:1'
            ],
            'hdd' => [
                'sometimes',
                'required',
                'numeric',
                'min:1'
            ],
            'iops' => [
                'sometimes',
                'required',
                'numeric',
                'min:1'
            ],
            'image_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.images,id,deleted_at,NULL',
            ],
        ];
    }

    /**
     * @return array
     */
    public function messages()
    {
        return [
            'required' => 'The :attribute field is required',
            'exists' => 'The specified :attribute was not found',
        ];
    }
}
