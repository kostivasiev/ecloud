<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\HostGroup;
use App\Models\V2\Instance;
use App\Rules\V2\ExistsForUser;
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

        $this->config = $instance->image->imageMetadata->pluck('key', 'value')->flip();

        $rules = [
            'name' => 'nullable|string',
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
            'backup_enabled' => 'sometimes|required|boolean',
            'host_group_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.host_groups,id,deleted_at,NULL',
                new ExistsForUser(HostGroup::class),
            ],
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
