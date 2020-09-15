<?php
namespace App\Http\Requests\V2;

use App\Models\V2\Vpc;
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
        $rules = [
            'name'    => 'nullable|string',
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id',
                new ExistsForUser(Vpc::class)
            ],
            'appliance_id' => 'sometimes|required|uuid|exists:ecloud.appliance_version,appliance_version_uuid',
            'vcpu_tier' => 'sometimes|required|string', # needs exists: adding once tier has been added to db
            'vcpu_cores' => 'sometimes|required|numeric|min:1',
            'ram_capacity' => 'sometimes|required|numeric|min:1024',
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
            'appliance_id.required' => 'The :attribute field is required',
            'appliance_id.exists' => 'The :attribute is not a valid Appliance',
            'vcpu_tier.required' => 'The :attribute field is required',
            'vcpu_count.required' => 'The :attribute field is required',
            'vcpu_count.min' => 'The :attribute field must be greater than or equal to one',
            'ram_capacity.required' => 'The :attribute field is required',
            'ram_capacity.min' => 'The :attribute field must be greater than or equal to 1024 megabytes',
        ];
    }
}
