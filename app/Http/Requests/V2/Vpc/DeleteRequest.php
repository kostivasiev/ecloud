<?php

namespace App\Http\Requests\V2\Vpc;

use App\Rules\V2\VpcHasResources;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class DeleteRequest
 * @package App\Http\Requests\V2\Vpc
 */
class DeleteRequest extends FormRequest
{
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
