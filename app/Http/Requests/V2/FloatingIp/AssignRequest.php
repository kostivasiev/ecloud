<?php

namespace App\Http\Requests\V2\FloatingIp;

use App\Rules\V2\ExistsForUser;
use Illuminate\Database\Eloquent\Relations\Relation;
use UKFast\FormRequests\FormRequest;

class AssignRequest extends FormRequest
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
            'id' => [
                'unique:ecloud.nats,destination_id,NULL,id,deleted_at,NULL',
                'unique:ecloud.nats,source_id,NULL,id,deleted_at,NULL'
            ],
            'resource_id' =>
                [
                    'required',
                    'string',
                    new ExistsForUser(array_values(Relation::morphMap()))
                ],
        ];
    }

    // Add fipId route parameter to validation data
    public function all($keys = null)
    {
        return array_merge(
            parent::all(),
            [
                'id' => app('request')->route('fipId')
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
        return [
            'resource_id.required' => 'The :attribute field is required',
            'id.unique' => 'The floating IP is already assigned'
        ];
    }
}
