<?php
namespace App\Http\Requests\V2\VolumeGroup;

use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    protected function rules()
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
        ];
    }
}
