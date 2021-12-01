<?php

namespace App\Http\Requests\V2\InstanceSoftware;

use App\Models\V2\Instance;
use App\Models\V2\Software;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsResourceAvailable;
use Illuminate\Validation\Rule;
use UKFast\FormRequests\FormRequest;

class Create extends FormRequest
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

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string'
            ],
            'instance_id' => [
                'required',
                'string',
                new ExistsForUser(Instance::class),
                new IsResourceAvailable(Instance::class),
            ],
            'software_id' => [
                'required',
                'string',
                Rule::exists(Software::class, 'id')->whereNull('deleted_at'),
                new ExistsForUser(Software::class),
            ],
        ];
    }
}
