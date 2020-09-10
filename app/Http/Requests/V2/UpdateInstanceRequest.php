<?php
namespace App\Http\Requests\V2;

use App\Models\V2\Instance;
use App\Models\V2\Network;
use App\Rules\V2\ExistsForUser;
use Illuminate\Support\Facades\Request;
use UKFast\FormRequests\FormRequest;

class UpdateInstanceRequest extends FormRequest
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
        $instance = Instance::findOrFail($this->instanceId);
        $rules = [
            'name'    => 'nullable|string',
            'network_id' => [
                'sometimes',
                'required_without:vpc_id',
                'nullable',
                'string',
                'exists:ecloud.networks,id',
                new ExistsForUser(Network::class)
            ],
            'vpc_id' => [
                'sometimes',
                'required_without:network_id',
                'nullable',
                'string',
                'exists:ecloud.vpcs,id',
            ]
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
            'name.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.exists' => 'The specified network was not found',
        ];
    }
}
