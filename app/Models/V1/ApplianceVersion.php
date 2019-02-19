<?php

namespace App\Models\V1;

use App\Rules\V1\IsValidUuid;
use App\Traits\V1\ColumnPrefixHelper;
use App\Traits\V1\UUIDHelper;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\DateTimeProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class ApplianceVersion extends Model implements Filterable, Sortable
{
    // Table columns have table name prefixes
    use ColumnPrefixHelper;

    // Table uses UUID's
    use UUIDHelper;

    protected $connection = 'ecloud';

    protected $table = 'appliance_version';

    // Use UUID as primary key
    protected $primaryKey = 'appliance_version_uuid';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    // Automatically manage our timestamps
    public $timestamps = true;

    const CREATED_AT = 'appliance_version_created_at';

    const UPDATED_AT = 'appliance_version_updated_at';

    // Validation Rules
    public static $rules = [
        'version' => ['required', 'max:25'],
        'script_template' => ['required'],
        'active' => ['nullable']
    ];


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
        'appliance_version_script_template',
        'appliance_version_active',
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
            'script_template' => 'appliance_version_script_template',
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
    public function filterableColumns($factory)
    {
        return [
            $factory->create('version', Filter::$stringDefaults),
            $factory->create('script_template', Filter::$stringDefaults),
            $factory->create('active', Filter::$enumDefaults),
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
            $factory->create('version'),
            $factory->create('active'),
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
            StringProperty::create('appliance_version_version', 'version'),
            StringProperty::create('appliance_version_script_template', 'script_template'),
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
        return Appliance::select('appliance_uuid')
            ->where('appliance_id', '=', $this->attributes['appliance_version_appliance_id'])
            ->first()
            ->uuid;
    }

    /**
     * Convenience mutator:
     * When we try and set our non-database appliance_uuid column, it saves the internal id to the
     * appliance_version_appliance_id database column
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
}
