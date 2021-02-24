<?php

namespace App\Models\V1;

use App\Events\V1\ApplianceVersionDeletedEvent;
use App\Exceptions\V1\ApplianceServerLicenseNotFoundException;
use App\Rules\V1\IsValidUuid;
use App\Traits\V1\ColumnPrefixHelper;
use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class ApplianceVersion extends Model implements Filterable, Sortable
{
    // Table columns have table name prefixes
    use ColumnPrefixHelper;

    // Table uses UUID's
    use UUIDHelper;

    use SoftDeletes;

    protected $connection = 'ecloud';

    protected $table = 'appliance_version';

    protected $keyType = 'string';
    // Use UUID as primary key
    protected $primaryKey = 'appliance_version_uuid';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    // Automatically manage our timestamps
    public $timestamps = true;

    const CREATED_AT = 'appliance_version_created_at';

    const UPDATED_AT = 'appliance_version_updated_at';

    const DELETED_AT = 'appliance_version_deleted_at';

    // Events triggered by actions on the model
    protected $dispatchesEvents = [
        //Trigger on deleting (not deleted, as we need the version record to cascade the soft-deletes to the parameters)
        'deleting' => ApplianceVersionDeletedEvent::class,
    ];

    // Validation Rules
    public static $rules = [
        'version' => ['required', 'integer'],
        'script_template' => ['nullable'],
        'vm_template' => ['required'],
        'description' => ['nullable'],
        'os_license_id' => ['required', 'integer'],
        'active' => ['nullable']
    ];

    /**
     * Return Create resource validation rules
     * @return array
     */
    public static function getRules()
    {
        $rules = static::$rules;
        $rules['appliance_id'] = ['required', new IsValidUuid()];
        return $rules;
    }

    /**
     * Return Update resource validation rules
     * @return array
     */
    public static function getUpdateRules()
    {
        $rules = static::$rules;
        // Modify our appliance version validation rules for an update
        $rules = array_merge(
            $rules,
            [
                'version' => ['nullable', 'integer'],
                'script_template' => ['nullable'],
                'vm_template' => ['filled'], //If it's present, we need a value.
                'os_license_id' => ['nullable', 'integer'],
                'id' => [new IsValidUuid()],
                'appliance_id' => ['nullable', new IsValidUuid()]
            ]
        );

        return $rules;
    }


    /**
     * The attributes included in the model's JSON form.
     * Admin scope / everything
     *
     * @var array
     */
    protected $visible = [
        'appliance_version_uuid',
        'appliance_uuid',
        'appliance_version_version',
        'appliance_version_description',
        'appliance_version_script_template',
        'appliance_version_vm_template',
        'appliance_version_server_license_id',
        'appliance_version_active',
        'appliance_version_created_at',
        'appliance_version_updated_at'
    ];

    /**
     * Restrict visibility for non-admin
     */
    const VISIBLE_SCOPE_RESELLER = [
        'appliance_version_uuid',
        'appliance_uuid',
        'appliance_version_script_template',
        'appliance_version_description',
        'appliance_version_created_at',
        'appliance_version_updated_at'
    ];

    /**
     * Non-database attributes
     * @var array
     */
    protected $appends = [
        // Return the foreign key value stored in appliance_version_appliance_id as a UUID
        'appliance_uuid'
    ];

    /**
     * Ditto configuration
     * ----------------------
     */

    /**
     * Ditto maps raw database names to friendly names.
     * @return array
     */
    public function databaseNames()
    {
        return [
            'id' => 'appliance_version_uuid', //UUID, not internal id
            'appliance_id' => 'appliance_uuid',
            'version' => 'appliance_version_version',
            'description' => 'appliance_version_description',
            'script_template' => 'appliance_version_script_template',
            'vm_template' => 'appliance_version_vm_template',
            'os_license_id' => 'appliance_version_server_license_id',
            'active' => 'appliance_version_active',
            'created_at' => 'appliance_version_created_at',
            'updated_at' => 'appliance_version_updated_at'
        ];
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('version', Filter::$stringDefaults),
            $factory->create('script_template', Filter::$stringDefaults),
            $factory->create('vm_template', Filter::$stringDefaults),
            $factory->create('os_license_id', Filter::$numericDefaults),
            $factory->boolean()->create('active', 'Yes', 'No'),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults)
        ];
    }


    /**
     * Ditto sorting configuration
     * @param SortFactory $factory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('version'),
            $factory->create('active'),
            $factory->create('vm_template'),
            $factory->create('os_license_id'),
            $factory->create('created_at'),
            $factory->create('updated_at')
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $sortFactory)
    {
        return [
            $sortFactory->create('created_at', 'desc'),
        ];
    }


    /**
     * Ditto Selectable persistent Properties
     * @return array
     */
    public function persistentProperties()
    {
        return ['id'];
    }


    /**
     * Resource package
     * Map request property to database field
     *
     * @return array
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        return [
            IdProperty::create('appliance_version_uuid', 'id', null, 'uuid'),
            //Return the appliance UUID, not internal ID
            StringProperty::create('appliance_uuid', 'appliance_id'),
            IntProperty::create('appliance_version_version', 'version'),
            StringProperty::create('appliance_version_description', 'description'),
            StringProperty::create('appliance_version_script_template', 'script_template'),
            StringProperty::create('appliance_version_vm_template', 'vm_template'),
            IntProperty::create('appliance_version_server_license_id', 'os_license_id'),
            BooleanProperty::create('appliance_version_active', 'active', null, 'Yes', 'No'),
            DateTimeProperty::create('appliance_version_created_at', 'created_at'),
            DateTimeProperty::create('appliance_version_updated_at', 'updated_at')
        ];
    }

    /**
     * appliance_uuid is our non-database foreign key appliance_id as the appliance UUID
     * Queries for the appliance_version_appliance_id return the record's UUID, not the internal ID stored in the column
     * @return mixed
     * @see also setApplianceVersionApplianceUuidAttribute($value)
     */
    public function getApplianceUuidAttribute()
    {
        $appliance = Appliance::select('appliance_uuid')
            ->where('appliance_id', '=', $this->attributes['appliance_version_appliance_id']);

        if ($appliance->count() > 0) {
            return $appliance->first()->uuid;
        }

        // Appliance with that id was not found
        return null;
    }

    /**
     * Convenience mutator:
     * When we try and set our non-database appliance_uuid column, it saves the internal id to the
     * appliance_version_appliance_id database foreign key column
     * @param $value
     */
    public function setApplianceVersionApplianceUuidAttribute($value)
    {
        $this->attributes['appliance_version_appliance_id'] =
            Appliance::select('appliance_id')
                ->where('appliance_uuid', '=', $value)
                ->first()->appliance_id;
    }


    /**
     * Relation mapping: applianceVersion to appliance resource
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function appliance()
    {
        return $this->hasOne(
            'App\Models\V1\Appliance',
            'appliance_id',
            'appliance_version_appliance_id'
        );
    }

    /**
     * Return the parameters for the appliance version
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parameters()
    {
        return $this->hasMany(
            'App\Models\V1\ApplianceParameter',
            'appliance_script_parameters_appliance_version_id',
            'appliance_version_id'
        );
    }

    /**
     * Return the VM Template associated with the Appliance version
     * @return mixed
     */
    public function getTemplateName()
    {
        return $this->vm_template;
    }


    /**
     * Return the script parameters for the Appliance version
     * @return array
     */
    public function getParameters()
    {
        $params = [];
        $parameters = $this->parameters()->get();
        foreach ($parameters as $parameter) {
            $params[$parameter->key] = $parameter;
        }

        return $params;
    }

    /**
     * Return a list of required parameters
     * @param bool $requiredOnly
     * @return array
     */
    public function getParameterList($requiredOnly = false)
    {
        $parameters = $this->parameters();

        if ($requiredOnly) {
            $parameters->where('appliance_script_parameters_required', '=', 'Yes');
        }

        return $parameters->pluck('appliance_script_parameters_key')
            ->toArray();
    }

    /**
     * Returns the server license associated with the appliance version
     * @return ServerLicense | null
     * @throws ApplianceServerLicenseNotFoundException
     */
    public function getLicense()
    {
        if (!empty($this->server_license_id)) {
            $serverLicense = ServerLicense::find($this->server_license_id);

            if (!empty($serverLicense)) {
                return $serverLicense;
            }
        }

        throw new ApplianceServerLicenseNotFoundException(
            "No Server license found for Appliance version '" . $this->getKey() . "'"
        );
    }

    /**
     * Return the data for the appliance version
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function data()
    {
        return $this->hasMany(
            'App\Models\V1\Appliance\Version\Data',
            'appliance_version_uuid',
            'appliance_version_uuid'
        );
    }

    /**
     * Return array of the data for the Appliance version
     * @return array
     */
    public function getDataArray()
    {
        $versionData = $this->data()->get();
        if (empty($versionData)) {
            return [];
        }

        $returnData = [];
        foreach ($versionData as $data) {
            $returnData[$data->key] = $data->value;
        }

        return $returnData;
    }
}
