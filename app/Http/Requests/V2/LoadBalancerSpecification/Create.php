<?php

namespace App\Http\Requests\V2\LoadBalancerSpecification;

use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
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
                'nullable',
                'string',
                'unique:ecloud.load_balancer_specifications,id',
            ],
            'description' => [
                'string'
            ],
            'node_count' => [
                'required',
                'numeric',
                'min:1'
            ],
            'cpu' => [
                'required',
                'numeric',
                'min:1'
            ],
            'ram' => [
                'required',
                'numeric',
                'min:1'
            ],
            'hdd' => [
                'required',
                'numeric',
                'min:1'
            ],
            'iops' => [
                'required',
                'numeric',
                'min:1'
            ],
            'image_id' => [
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
