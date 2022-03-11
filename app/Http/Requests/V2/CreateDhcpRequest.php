<?php

namespace App\Http\Requests\V2;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class CreateDhcpsRequest
 * @package App\Http\Requests\V2
 */
class CreateDhcpRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsResourceAvailable(Vpc::class),
            ],
            'availability_zone_id' => [
                'required',
                'string',
                'exists:ecloud.availability_zones,id,deleted_at,NULL',
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
            'vpc_id.required' => 'The :attribute field is required',
            'availability_zone_id.required' => 'The :attribute field is required',
            'availability_zone_id.exists' => 'The specified :attribute was not found',
        ];
    }
}
