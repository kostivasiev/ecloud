<?php

namespace App\Http\Requests\V2\Vpc;

use App\Rules\V2\IsMaxVpcLimitReached;
use UKFast\FormRequests\FormRequest;

/**
 * Class DefaultsRequest
 * @package App\Http\Requests\V2\Vpc
 */
class DefaultsRequest extends FormRequest
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
            'availability_zone_id' => 'required|string|exists:ecloud.availability_zones,id,deleted_at,NULL',
        ];
    }
}
