<?php

namespace App\Models\V1;

use App\Rules\V1\IsValidUuid;
use App\Traits\V1\ColumnPrefixHelper;
use App\Traits\V1\UUIDHelper;
use App\Rules\V1\IsValidValidationRule;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\DateTimeProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class ApplianceParameters extends Model implements Filterable, Sortable
{
    // Table columns have table name prefixes
    use ColumnPrefixHelper;

    // Table uses UUID's
    use UUIDHelper;

    protected $connection = 'ecloud';

    protected $table = 'appliance_script_parameters';

    // Use UUID as primary key
    protected $primaryKey = 'appliance_script_parameters_uuid';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    // Automatically manage our timestamps
    public $timestamps = true;

    const CREATED_AT = 'appliance_script_parameters_created_at';

    const UPDATED_AT = 'appliance_script_parameters_updated_at';

    /**
     * Validation rules
     * @see function rules()
     * @var array
     */
    public static $rules = [
        'name' => ['required', 'max:255'],
        'type' => ['required', 'in:String,Numeric,Boolean,Array,Password,Date,DateTime'],
        'required' => ['nullable'], ['boolean']
    ];

    /**
     * Return model validation rules
     * @return array
     */
    public static function getRules()
    {
        $rules = static::$rules;
        $rules['validation_rule'] = ['filled', new IsValidValidationRule()];
        return $rules;
    }

    /**
     * The attributes included in the model's JSON form.
     * Admin scope / everything
     *
     * @var array
     */
    protected $visible = [
        'appliance_script_parameters_uuid',
        'appliance_version_uuid',
        'appliance_script_parameters_name',
        'appliance_script_parameters_type',
        'appliance_script_parameters_required',
        'appliance_script_parameters_validation_rule',
        'appliance_script_parameters_created_at',
        'appliance_script_parameters_updated_at',
    ];

    /**
     * Non-database attributes
     * @var array
     */
    protected $appends = [
        // Return the foreign key value stored in appliance_version_appliance_id as a UUID
        'appliance_version_uuid'
    ];


    /**
     * Getter for appliance_version_uuid
     */
    public function getApplianceVersionUuidAttribute()
    {
        $applianceVersion = ApplianceVersion::select('appliance_version_uuid')
            ->where('appliance_version_id', '=', $this->attributes['appliance_script_parameters_appliance_version_id']);

        if ($applianceVersion->count() > 0) {
            return $applianceVersion->first()->uuid;
        }
        return;
    }

    /**
     * Setter for appliance_version_uuid
     * @param $value
     */
    public function setApplianceScriptParametersApplianceVersionUuidAttribute($value)
    {
        $this->attributes['appliance_script_parameters_appliance_version_id'] =
            ApplianceVersion::select('appliance_version_id')
                ->where('appliance_version_uuid', '=', $value)
                ->first()->appliance_version_id ;
    }

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
            'id' => 'appliance_script_parameters_uuid', //UUID, not internal id
            'version_id' => 'appliance_version_uuid',
            'name' => 'appliance_script_parameters_name',
            'type' => 'appliance_script_parameters_type',
            'required' => 'appliance_script_parameters_required',
            'validation_rule' => 'appliance_script_parameters_validation_rule',
            'created_at' => 'appliance_script_parameters_created_at',
            'updated_at' => 'appliance_script_parameters_updated_at'
        ];
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
            $factory->create('version_id', Filter::$stringDefaults),
            $factory->create('required', Filter::$enumDefaults),
            $factory->create('validation_rule', Filter::$stringDefaults),
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
    public function sortableColumns($factory)
    {
        return [
            $factory->create('name'),
            $factory->create('type'),
            $factory->create('version_id'),
            $factory->create('required'),
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
    public function defaultSort($sortFactory)
    {
        return [
            $sortFactory->create('name', 'desc'),
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
            IdProperty::create('appliance_script_parameters_uuid', 'id', null, 'uuid'),
            // Return the parameter UUID, not internal ID
            StringProperty::create('appliance_version_uuid', 'version_id'),
            StringProperty::create('appliance_script_parameters_name', 'name'),
            StringProperty::create('appliance_script_parameters_type', 'type'),
            BooleanProperty::create('appliance_script_parameters_required', 'required', null, 'Yes', 'No'),
            StringProperty::create('appliance_script_parameters_validation_rule', 'validation_rule'),
            DateTimeProperty::create('appliance_script_parameters_created_at', 'created_at'),
            DateTimeProperty::create('appliance_script_parameters_updated_at', 'updated_at')
        ];
    }

    /**
     * Save the required parameter as yes/no
     * @param $value
     */
    public function setApplianceScriptParametersRequiredAttribute($value)
    {
        $this->attributes['appliance_script_parameters_required'] = ($value) ? 'Yes' : 'No';
    }



    /**
     * Relation mapping: script param to applianceVersion
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function applianceVersion()
    {
        return $this->hasOne(
            'App\Models\V1\ApplianceVersion',
            'appliance_version_id',
            'appliance_script_parameters_appliance_version_id'
        )->first();
    }
}
