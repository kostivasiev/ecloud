<?php

namespace App\Models\V1;

use App\Events\V1\ApplianceDeletedEvent;
use App\Exceptions\V1\ApplianceVersionNotFoundException;
use App\Traits\V1\ColumnPrefixHelper;
use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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

class Appliance extends Model implements Filterable, Sortable
{
    use HasFactory;

    // Table columns have table name prefixes
    use ColumnPrefixHelper;

    // Table uses UUID's
    use UUIDHelper;

    use SoftDeletes;

    protected $connection = 'ecloud';

    protected $table = 'appliance';

    protected $keyType = 'string';
    // Use UUID as primary key
    protected $primaryKey = 'appliance_uuid';
    // Don't increment the primary key for UUID's
    public $incrementing = false;

    // Automatically manage our timestamps
    public $timestamps = true;

    const CREATED_AT = 'appliance_created_at';

    const UPDATED_AT = 'appliance_updated_at';

    const DELETED_AT = 'appliance_deleted_at';

    // Events triggered by actions on the model
    protected $dispatchesEvents = [
        'deleting' => ApplianceDeletedEvent::class, //Trigger on deleting an appliance to cascade the delete to
    ];

    /**
     * Non-database attributes
     * @var array
     */
    protected $appends = [
        // Return the latest version of the appliance
        'version'
    ];

    // Validation Rules
    public static $rules = [
        'name' => ['required', 'max:255'],
        'logo_uri' => ['nullable', 'max:255'],
        'description' => ['nullable'],
        'documentation_uri' => ['nullable'],
        'publisher' => ['nullable', 'max:255'],
        'active' => ['nullable', 'boolean'],
        'public' => ['nullable', 'boolean']
    ];

    /**
     * The attributes included in the model's JSON form.
     * Admin scope / everything
     *
     * @var array
     */
    protected $visible = [
        'appliance_uuid',
        'appliance_name',
        'version',
        'appliance_logo_uri',
        'appliance_description',
        'appliance_documentation_uri',
        'appliance_publisher',
        'appliance_active',
        'appliance_is_public',
        'appliance_created_at',
        'appliance_updated_at',
    ];

    /**
     * Restrict visibility for non-admin
     */
    const VISIBLE_SCOPE_RESELLER = [
        'appliance_uuid',
        'appliance_name',
        'appliance_logo_uri',
        'appliance_description',
        'appliance_documentation_uri',
        'appliance_publisher',
        'appliance_created_at'
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
            'id' => 'appliance_uuid', //UUID, not internal id
            'name' => 'appliance_name',
            'version' => 'version', //Non-database attribute
            'logo_uri' => 'appliance_logo_uri',
            'description' => 'appliance_description',
            'documentation_uri' => 'appliance_documentation_uri',
            'publisher' => 'appliance_publisher',
            'active' => 'appliance_active', // Yes / No
            'public' => 'appliance_is_public', // Yes / No
            'created_at' => 'appliance_created_at',
            'updated_at' => 'appliance_updated_at',
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
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('version', Filter::$stringDefaults),
            $factory->create('description', Filter::$stringDefaults),
            $factory->create('publisher', Filter::$stringDefaults),
            $factory->boolean()->create('active', 'Yes', 'No'),
            $factory->boolean()->create('public', 'Yes', 'No'),
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
            $factory->create('name'),
            $factory->create('version'),
            $factory->create('publisher'),
            $factory->create('active'),
            $factory->create('public'),
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
            $sortFactory->create('name', 'asc'),
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
            IdProperty::create('appliance_uuid', 'id', null, 'uuid'),
            StringProperty::create('appliance_name', 'name'),
            IntProperty::create('version', 'version'),
            StringProperty::create('appliance_logo_uri', 'logo_uri'),
            StringProperty::create('appliance_description', 'description'),
            StringProperty::create('appliance_documentation_uri', 'documentation_uri'),
            StringProperty::create('appliance_publisher', 'publisher'),
            BooleanProperty::create('appliance_active', 'active', null, 'Yes', 'No'),
            BooleanProperty::create('appliance_is_public', 'public', null, 'Yes', 'No'),
            DateTimeProperty::create('appliance_created_at', 'created_at'),
            DateTimeProperty::create('appliance_updated_at', 'updated_at')
        ];
    }

    /**
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versions()
    {
        return $this->hasMany(
            'App\Models\V1\ApplianceVersion',
            'appliance_version_appliance_id',
            'appliance_id'
        );
    }

    /**
     * Return the Pods that the appliance is active in.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pods()
    {
        return $this->hasMany(
            'App\Models\V1\AppliancePodAvailability',
            'appliance_pod_availability_appliance_id',
            'appliance_id'
        );
    }


    /**
     * Get the latest version of the appliance.
     * @return Model|\Illuminate\Database\Eloquent\Relations\HasMany|object|null
     * @throws ApplianceVersionNotFoundException
     */
    public function getLatestVersion()
    {
        $version = $this->versions()->orderBy('appliance_version_version', 'DESC')->limit(1);

        if ($version->get()->count() > 0) {
            return $version->first();
        }

        throw new ApplianceVersionNotFoundException(
            'Unable to load latest version of the appliance. No versions were found.'
        );
    }

    /**
     * Get designation of the latest version of he application
     * @return string
     */
    public function getVersionAttribute()
    {
        try {
            $version = $this->getLatestVersion();
            return $version->version;
        } catch (ApplianceVersionNotFoundException $exception) {
            return 0;
        }
    }
}
