<?php

namespace App\Http\Requests\V2\Script;

use App\Models\V2\Script;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Update extends FormRequest
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
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
            ],
            'sequence' => [
                'sometimes',
                'required',
                'integer',
                Rule::unique(Script::class)->where(function ($query) {
                    return $query->where('software_id', app('request')->input('software_id'));
                })->whereNull('deleted_at'),
            ],
            'script' => [
                'sometimes',
                'required',
                'string',
            ]
        ];
    }
}
