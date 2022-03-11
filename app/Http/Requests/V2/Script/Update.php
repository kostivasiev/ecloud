<?php

namespace App\Http\Requests\V2\Script;

use App\Models\V2\Script;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class Update extends FormRequest
{
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
