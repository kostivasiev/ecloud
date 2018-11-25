<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IntProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Host extends Model implements Filterable, Sortable
{
    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'ucs_node';
    protected $primaryKey = 'ucs_node_id';
    public $timestamps = false;


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
        $names = [];

        foreach ($this->properties() as $property) {
            $names[$property->getFriendlyName()] = $property->getDatabaseName();
        }

        return $names;
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
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
            $factory->create('id'),
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
            $sortFactory->create('id', 'asc'),
        ];
    }


    /**
     * Resource configuration
     * ----------------------
     */

    /**
     * Map request property to database field
     *
     * @return array
     */
    public function properties()
    {
        return [
            IdProperty::create('ucs_node_id', 'id'),
            IntProperty::create('ucs_node_reseller_id', 'reseller_id'),

            IntProperty::create('ucs_node_ucs_reseller_id', 'solution_id'),
            IntProperty::create('ucs_node_datacentre_id', 'datacentre_id'),

            StringProperty::create('ucs_node_specification', 'specification'),
        ];
    }

    /**
     * End Package Config
     * ----------------------
     */

    /**
     * Scope a query for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ucs_node_reseller_id', $resellerId);
        }

        return $query;
    }
}
