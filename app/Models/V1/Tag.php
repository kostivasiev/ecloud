<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use UKFast\Api\Resource\Property\DateTimeProperty;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class Tag extends Model implements Filterable, Sortable
{
    const KEY_FORMAT_DESC = 'alphanumeric characters and hyphens';
    const KEY_FORMAT_REGEX = '^[a-z0-9]+(?:-[a-z0-9]+)*$';

    /**
     * Eloquent configuration
     * ----------------------
     */

    protected $table = 'metadata';
    protected $primaryKey = 'metadata_id';
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
        $databaseNames = [];

        foreach ($this->properties() as $property) {
            if (is_array($property)) {
                foreach ($property as $subProperty) {
                    $databaseNames[$subProperty->getFriendlyName()] = $subProperty->getDatabaseName();
                }
                continue;
            }

            $databaseNames[$property->getFriendlyName()] = $property->getDatabaseName();
        }

        return $databaseNames;
    }

    /**
     * Ditto filtering configuration
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('key', Filter::$stringDefaults),
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
            $factory->create('key'),
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
            $sortFactory->create('key', 'asc'),
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
        $properties = [
            StringProperty::create('metadata_key', 'key'),
            StringProperty::create('metadata_value', 'value'),
            DateTimeProperty::create('metadata_created', 'created_at'),
        ];

        $request = app('request');
        if (!$request->user()->isAdmin()) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            IdProperty::create('metadata_id', 'id'),
            IntProperty::create('metadata_reseller_id', 'reseller_id'),

            StringProperty::create('metadata_resource', 'resource_type'),
            IntProperty::create('metadata_resource_id', 'resource_id'),

            StringProperty::create('metadata_createdby', 'created_by'),
            IntProperty::create('metadata_createdby_id', 'created_id'),
        ]);
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
            $query->where('metadata_reseller_id', $resellerId);
        }

        return $query;
    }

    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('metadata_resource', 'ucs_reseller')
            ->where('metadata_resource_id', $solutionId);

        return $query;
    }

    public function scopeWithServer($query, $serverId)
    {
        return $query->where('metadata_resource', 'server')
            ->where('metadata_resource_id', $serverId);
    }

    public function scopeWithKey($query, $key)
    {
        return $query->where('metadata_key', $key);
    }
}
