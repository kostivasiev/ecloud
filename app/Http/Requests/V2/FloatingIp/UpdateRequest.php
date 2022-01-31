<?php

namespace App\Http\Requests\V2\FloatingIp;

use App\Rules\V2\IpAddress\IsAvailable;
use App\Rules\V2\IpAddress\IsInSubnet;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateFloatingIpRequest
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
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'hostname' => [
                'sometimes',
                'ip',
                new IsInSubnet(app('request')->input('hostname')),
                'bail',
                new IsAvailable(app('request')->input('hostname')),
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
