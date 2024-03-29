<?php

namespace App\Http\Requests\V2\Script;

use App\Models\V2\Script;
use App\Models\V2\Software;
use App\Rules\V2\ExistsForUser;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class Create extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
            ],
            'software_id' => [
                'required',
                'string',
                Rule::exists(Software::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(Software::class),
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
                'required',
                'string',
            ]
        ];
    }
}
