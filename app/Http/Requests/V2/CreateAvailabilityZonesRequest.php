<?php
namespace App\Http\Requests\V2;

use UKFast\FormRequests\FormRequest;

class CreateAvailabilityZonesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return ($this->user()->isAdmin());
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'code'    => 'required|string',
            'name'    => 'required|string',
            'site_id' => 'required|integer',
        ];
    }

}
