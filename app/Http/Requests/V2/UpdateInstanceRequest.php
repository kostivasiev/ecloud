<?php
namespace App\Http\Requests\V2;

use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class UpdateInstanceRequest extends FormRequest
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
            'network_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.networks,id',
                new ExistsForUser(Network::class)
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
        return [
            'network_id.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.exists' => 'The specified network was not found',
        ];
    }
}
