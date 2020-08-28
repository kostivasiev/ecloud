<?php
namespace App\Http\Requests\V2;

use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use UKFast\FormRequests\FormRequest;

class CreateInstanceRequest extends FormRequest
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
            'name'    => 'nullable|string',
            'network_id' => [
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
            'network_id.required' => 'The :attribute field is required',
        ];
    }
}
