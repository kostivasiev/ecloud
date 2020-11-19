<?php

namespace App\Http\Requests\V2\Vpc;

use App\Rules\V2\VpcHasResources;
use UKFast\FormRequests\FormRequest;

/**
 * Class DeleteRequest
 * @package App\Http\Requests\V2\Vpc
 */
class DeleteRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'vpc_id' => new VpcHasResources(),
        ];
    }

    // Add vpcId route parameter to validation data
    public function all($keys = null)
    {
        return array_merge(
            parent::all(),
            [
                'vpc_id' => app('request')->route('vpcId')
            ]
        );
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
