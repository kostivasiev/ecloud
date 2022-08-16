<?php

namespace App\Http\Requests\V2\Instance;

use App\Models\V2\FloatingIp;
use App\Models\V2\HostGroup;
use App\Models\V2\Image;
use App\Models\V2\Network;
use App\Models\V2\ResourceTier;
use App\Models\V2\Software;
use App\Models\V2\SshKeyPair;
use App\Models\V2\Vpc;
use App\Rules\V2\ExistsForUser;
use App\Rules\V2\FloatingIp\IsAssigned;
use App\Rules\V2\HasHosts;
use App\Rules\V2\HostGroup\HostGroupCanProvision;
use App\Rules\V2\Instance\SoftwarePlatformMatchesImagePlatform;
use App\Rules\V2\IsMaxInstanceForVpc;
use App\Rules\V2\IsResourceAvailable;
use App\Rules\V2\IsSameAvailabilityZone;
use App\Rules\V2\IsSameVpc;
use App\Rules\V2\IsValidRamMultiple;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateRequest extends FormRequest
{
    protected $image;
    protected $config;
    protected string $platform;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->image = Image::findOrFail($this->request->get('image_id'));
        $this->config = $this->image->imageMetadata->pluck('key', 'value')->flip();
        $this->platform = strtolower($this->image->platform);

        $network = Network::forUser(Auth::user())->findOrFail($this->request->get('network_id'));

        $rules = [
            'name' => 'nullable|string|max:255',
            'vpc_id' => [
                'required',
                'string',
                'exists:ecloud.vpcs,id,deleted_at,NULL',
                new ExistsForUser(Vpc::class),
                new IsMaxInstanceForVpc(),
                new IsResourceAvailable(Vpc::class),
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
            'host_group_id' => [
                'sometimes',
                'string',
                'exists:ecloud.host_groups,id,deleted_at,NULL',
                'bail',
                new ExistsForUser(HostGroup::class),
                new IsResourceAvailable(HostGroup::class),
                new HasHosts(),
                new HostGroupCanProvision($this->request->get('ram_capacity')),
                new IsSameAvailabilityZone($network->router->availabilityZone),
            ],
            'resource_tier_id' => [
                'sometimes',
                'required',
                'string',
                Rule::exists(ResourceTier::class, 'id')->whereNull('deleted_at')->where('active', true),
                new IsSameAvailabilityZone($network->router->availabilityZone),
            ],
            'network_id' => [
                'required',
                'string',
                'exists:ecloud.networks,id,deleted_at,NULL',
                new ExistsForUser(Network::class),
                new IsResourceAvailable(Network::class),
                new IsSameVpc($this->request->get('vpc_id')),
            ],
            'floating_ip_id' => [
                'prohibits:requires_floating_ip',
                'sometimes',
                'string',
                'exists:ecloud.floating_ips,id,deleted_at,NULL',
                new ExistsForUser(FloatingIp::class),
                new IsResourceAvailable(FloatingIp::class),
                new IsAssigned(),
                new IsSameAvailabilityZone($this->request->get('network_id'))
            ],
            'requires_floating_ip' => [
                'prohibits:floating_ip_id',
                'sometimes',
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
            'ssh_key_pair_ids.*' => [
                'sometimes',
                'string',
                new ExistsForUser(SshKeyPair::class),
            ],
            'software_ids' => [
                'sometimes',
                'required',
                'array'
            ],
            'software_ids.*' => [
                'required',
                'string',
                Rule::exists(Software::class, 'id')->whereNull('deleted_at'),
                new SoftwarePlatformMatchesImagePlatform($this->request->get('image_id'))
            ],
        ];

        if (Auth::user()->isAdmin()) {
            $rules['is_hidden'] = [
                'sometimes',
                'required',
                'boolean'
            ];
        };


        $rules = array_merge($rules, $this->generateImageDataRules());
        return $rules;
    }

    public function generateImageDataRules()
    {
        // Now for the dynamic rules for the appliance data
        $scriptRules = [];

        // So, we need to retrieve the validation rules
        $parameters = $this->image->imageParameters;
        foreach ($parameters as $parameterKey => $parameter) {
            $key = 'image_data.' . $parameter->key;
            $scriptRules[$key][] = ($parameter->required == 'Yes') ? 'required' : 'nullable';
            //validation rules regex
            if (!empty($parameter->validation_rule)) {
                $scriptRules[$key][] = 'regex:' . $parameter->validation_rule;
            }

            // For data types String,Numeric,Boolean we can use Laravel validation
            switch ($parameter->type) {
                case 'String':
                case 'Numeric':
                case 'Boolean':
                    $scriptRules[$key][] = strtolower($parameter->type);
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
            'floating_ip_id.prohibits' => 'requires_floating_ip cannot be specified if floating_ip_id is provided',
            'requires_floating_ip.prohibits' => 'floating_ip_id cannot be specified if requires_floating_ip is provided',
        ];
    }
}
