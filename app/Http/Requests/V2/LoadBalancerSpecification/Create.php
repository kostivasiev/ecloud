<?php

namespace App\Http\Requests\V2\LoadBalancerSpecification;

use UKFast\FormRequests\FormRequest;

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
            'node_count' => [
                'required',
                'numeric',
                'min:1'
            ],
            'cpu' => [
                'numeric',
                'min:1'
            ],
            'ram' => [
                'numeric',
                'min:1'
            ],
            'hdd' => [
                'numeric',
                'min:1'
            ],
            'iops' => [
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
