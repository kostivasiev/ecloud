<?php

namespace App\Models\V1;

use App\Traits\V1\ColumnPrefix;
use App\Traits\V1\UUIDHelper;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\BooleanProperty;
use UKFast\Api\Resource\Property\StringProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Appliance extends Model implements Filterable, Sortable
{
    // Table columns have table name prefixes
    use ColumnPrefix;

    // Table uses UUID's
    use UUIDHelper;

    protected $connection = 'ecloud';

    protected $table = 'appliance';

    protected $primaryKey = 'appliance_id';

    // Automatically manage our timestamps
    public $timestamps = true;

    const CREATED_AT = 'appliance_created_at';

    const UPDATED_AT = 'appliance_updated_at';


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
            'id' => 'appliance_uuid', //UUIDHelper, not internal id
            'name' => 'appliance_name',
            'logo_uri' => 'appliance_logo_url',
            'description' => 'appliance_description',
            'documentation_uri' => 'appliance_documentation_uri',
            'publisher' => 'appliance_publisher',
            'active' => 'appliance_active' // Yes / No
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
            $factory->create('description', Filter::$stringDefaults),
            $factory->create('publisher', Filter::$stringDefaults),
            $factory->create('active', Filter::$stringDefaults)
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
            $factory->create('publisher'),
            $factory->create('active')
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
     */
    public function properties()
    {
        return [
            StringProperty::create('appliance_uuid', 'id'),
            StringProperty::create('appliance_name', 'name'),
            StringProperty::create('appliance_logo_uri', 'logo_uri'),
            StringProperty::create('appliance_description', 'description'),
            StringProperty::create('appliance_documentation_uri', 'documentation_uri'),
            StringProperty::create('appliance_publisher', 'publisher'),
            BooleanProperty::create('appliance_active', 'active')
        ];
    }


    /**
     * Mutate the appliance_active property to a boolean
     * @param $value
     * @return bool
     */
    public function getApplianceActiveAttribute($value)
    {
        return ($value != 'No');
    }

}
