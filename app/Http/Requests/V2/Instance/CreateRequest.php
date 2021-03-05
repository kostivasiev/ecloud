<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\FloatingIp;
use App\Models\V2\Image;
use App\Models\V2\Network;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\IsValidRamMultiple;
use Illuminate\Support\Facades\Log;
use UKFast\FormRequests\FormRequest;

class CreateRequest extends FormRequest
{
    protected $image;
    protected $config;
    protected string $platform;

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
        $this->image = Image::findOrFail($this->request->get('image_id'));
        $this->config = $this->image->metadata->pluck('key', 'value')->flip();
        $this->platform = strtolower($this->image->platform);

        $rules = [
            'name' => 'nullable|string',
            'vpc_id' => [
                'sometimes',
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class)
            ],
            'image_id' => [
                'required',
                'string',
                'exists:ecloud.images,id,deleted_at,NULL'
            ],
            'vcpu_cores' => [
                'required',
                'numeric',
                'min:' . ($this->config->get('ukfast.spec.cpu_cores.min') ?? config('instance.cpu_cores.min')),
                'max:' . ($this->config->get('ukfast.spec.cpu_cores.max') ?? config('instance.cpu_cores.max')),
            ],
            'ram_capacity' => [
                'required',
                'numeric',
                'min:' . ($this->config->get('ukfast.spec.ram.min') ?? config('instance.ram_capacity.min')),
                'max:' . ($this->config->get('ukfast.spec.ram.max') ?? config('instance.ram_capacity.max')),
                new IsValidRamMultiple()
            ],
            'locked' => 'sometimes|required|boolean',
            'backup_enabled' => 'sometimes|required|boolean',
            'network_id' => [
                'sometimes',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
            ],
            'floating_ip_id' => [
                'sometimes',
                'string',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                'required_without:requires_floating_ip',
                new ExistsForUser(FloatingIp::class),
            ],
            'requires_floating_ip' => [
                'sometimes',
                'required_without:floating_ip_id',
                'boolean',
            ],
            'user_script' => [
                'sometimes',
                'required',
                'string',
            ],
            'volume_capacity' => [
                'sometimes',
                'required',
                'integer',
                'min:' . ($this->config->get('ukfast.spec.volume.min') ?? config('volume.capacity.' . $this->platform . '.min')),
                'max:' . ($this->config->get('ukfast.spec.volume.max') ?? config('volume.capacity.max')),
            ],
            'volume_iops' => [
                'sometimes',
                'required',
                'numeric',
                'in:300,600,1200,2500',
            ],
        ];

        $rules = array_merge($rules, $this->generateApplianceRules());
        return $rules;
    }

    public function generateApplianceRules()
    {
        // Now for the dynamic rules for the appliance data
        $scriptRules = [];

        // So, we need to retrieve the validation rules
        $parameters = $this->image->parameters;
        foreach ($parameters as $parameterKey => $parameter) {
            $key = 'image_data.' . $parameterKey;
            $scriptRules[$key][] = ($parameter->appliance_script_parameters_required == 'Yes') ? 'required' : 'nullable';
            //validation rules regex
            if (!empty($parameters[$parameterKey]->appliance_script_parameters_validation_rule)) {
                $scriptRules[$key][] = 'regex:' . $parameters[$parameterKey]->appliance_script_parameters_validation_rule;
            }

            // For data types String,Numeric,Boolean we can use Laravel validation
            switch ($parameters[$parameterKey]->appliance_script_parameters_type) {
                case 'String':
                case 'Numeric':
                case 'Boolean':
                    $scriptRules[$key][] = strtolower($parameters[$parameterKey]->appliance_script_parameters_type);
                    break;
                case 'Password':
                    $scriptRules[$key][] = 'string';
            }
        }

        return $scriptRules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array|string[]
     */
    public function messages()
    {
        return [
            // TODO: Clean these up - so many duplicates :/
            'required' => 'The :attribute field is required',
            'vpc_id.exists' => 'No valid Vpc record found for specified :attribute',
            'image_id.exists' => 'The :attribute is not a valid Image',
            'vcpu_cores.required' => 'The :attribute field is required',
            'availability_zone_id.exists' => 'No valid Availability Zone exists for :attribute',
            'network_id.required' => 'The :attribute field, when specified, cannot be null',
            'network_id.exists' => 'The specified :attribute was not found',
            'floating_ip_id.required' => 'The :attribute field, when specified, cannot be null',
            'floating_ip_id.exists' => 'The specified :attribute was not found',
            'image_data.required' => 'The :attribute field, when specified, cannot be null',
            'user_script.required' => 'The :attribute field, when specified, cannot be null',
            'volume_capacity.required' => 'The :attribute field, when specified, cannot be null',
            'ram_capacity.required' => 'The :attribute field is required',
            'volume_capacity.min' => 'Specified :attribute is below the minimum of ' .
                ($this->config->get('ukfast.spec.volume.min') ?? config('volume.capacity.' . $this->platform . '.min')),
            'volume_capacity.max' => 'Specified :attribute is above the maximum of ' .
                ($this->config->get('ukfast.spec.volume.max') ?? config('volume.capacity.max')),
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
