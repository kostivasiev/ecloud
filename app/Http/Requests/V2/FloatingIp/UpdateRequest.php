<?php

namespace App\Http\Requests\V2\FloatingIp;

use UKFast\Api\Validation\Rules\Dns\Records\Hostname;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateFloatingIpRequest
 * @package App\Http\Requests\V2
 */
class UpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'rdns_hostname' => [
                'sometimes',
                new Hostname(app('request')->input('hostname')),
            ],
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
