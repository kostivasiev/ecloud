<?php

namespace App\Http\Requests\V2\VpcSupport;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use UKFast\FormRequests\FormRequest;

/**
 * Class UpdateVirtualPrivateCloudsRequest
 * @package App\Http\Requests\V2\Vpc
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
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                'unique:ecloud.vpc_support,vpc_id,NULL,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'start_date' => 'sometimes|date_format:Y-m-d',
            'end_date' => 'sometimes|nullable|date_format:Y-m-d',
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
            'exists' => 'The specified :attribute was not found',
            'unique' => 'Support is already enabled for this VPC'
        ];
    }
}
