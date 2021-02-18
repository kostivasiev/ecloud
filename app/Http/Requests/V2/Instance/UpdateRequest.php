<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\Instance;
use App\Rules\V2\IsValidRamMultiple;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use UKFast\FormRequests\FormRequest;

class UpdateRequest extends FormRequest
{
    protected $instanceId;

    protected $config;

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
        $instance = Instance::forUser(Auth::user())
            ->findOrFail($this->instanceId);

        $this->config = $instance->applianceVersion->applianceVersionData->pluck('key', 'value')->flip();

        $rules = [
            'name' => 'nullable|string',
            /*            'vpc_id' => [
                            'sometimes',
                            'required',
                            'string',
                            'exists:ecloud.vpcs,id',
                            new ExistsForUser(Vpc::class)
                        ],*/
            /*            'appliance_id' => [
                            'sometimes',
                            'required',
                            'uuid',
                            'exists:ecloud.appliance,appliance_uuid'
                        ],*/
            'vcpu_cores' => [
                'sometimes',
                'required',
                'numeric',
                'min:' . ($this->config->get('ukfast.spec.cpu_cores.min') ?? config('instance.cpu_cores.min')),
                'max:' . ($this->config->get('ukfast.spec.cpu_cores.max') ?? config('instance.cpu_cores.max')),
            ],
            'ram_capacity' => [
                'sometimes',
                'required',
                'numeric',
                'min:' . ($this->config->get('ukfast.spec.ram.min') ?? config('instance.ram_capacity.min')),
                'max:' . ($this->config->get('ukfast.spec.ram.max') ?? config('instance.ram_capacity.max')),
                new IsValidRamMultiple()
            ],
            'locked' => 'sometimes|required|boolean',
            'backup_enabled' => 'sometimes|required|boolean',
            // 'platform' => 'sometimes|required|in:Windows,Linux',
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
            'required' => 'The :attribute field is required',
            'vpc_id.exists' => 'No valid Vpc record found for specified :attribute',
            'appliance_id.exists' => 'The :attribute is not a valid Appliance',
            'vcpu_cores.min' => 'Specified :attribute is below the minimum of '
                . ($this->config->get('ukfast.spec.cpu_cores.min') ?? config('instance.cpu_cores.min')),
            'vcpu_cores.max' => 'Specified :attribute is above the maximum of '
                . ($this->config->get('ukfast.spec.cpu_cores.max') ?? config('instance.cpu_cores.max')),
            'ram_capacity.min' => 'Specified :attribute is below the minimum of '
                . ($this->config->get('ukfast.spec.ram.min') ?? config('instance.ram_capacity.min')),
            'ram_capacity.max' => 'Specified :attribute is above the maximum of '
                . ($this->config->get('ukfast.spec.ram.max') ?? config('instance.ram_capacity.max')),
        ];
    }
}
