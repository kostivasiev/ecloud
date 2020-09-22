<?php
namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use Illuminate\Support\Facades\Request;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{

    protected $instanceId;

    public function __construct(
        array $query = [],
        array $request = [],
        array $attributes = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ) {
        parent::__construct($query, $request, $attributes, $cookies, $files, $server, $content);
        $this->instanceId = Request::route('instanceId');
    }

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
        $rules = [
            'name'    => 'nullable|string',
            'vpc_id' => [
                'sometimes',
                'nullable',
                'string',
                'exists:ecloud.vpcs,id',
                new ExistsForUser(Vpc::class)
            ],
            'locked' => 'sometimes|required|boolean',
        ];

        return $rules;
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
            'vpc_id.exists' => 'No valid Vpc record found for specified :attribute',
        ];
    }
}
