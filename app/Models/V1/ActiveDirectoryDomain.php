<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class ActiveDirectoryDomain extends Model implements Filterable, Sortable
{
    /**
     * Eloquent configuration
     * ----------------------
     */
    protected $table = 'ad_domain';
    protected $primaryKey = 'ad_domain_id';

    public $timestamps = false;

    protected $casts = [
        'ad_domain_id' => 'integer',
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
            'id' => 'ad_domain_id',
            'name' => 'ad_domain_name',
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
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('name', Filter::$stringDefaults),
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
            $factory->create('id'),
            $factory->create('name'),
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
            $sortFactory->create('id', 'asc'),
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
        $properties = [
            IdProperty::create('ad_domain_id', 'id'),
            StringProperty::create('ad_domain_name', 'name'),
        ];

        $request = app('request');
        if (!$request->user()->isAdmin()) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            IntProperty::create('ad_domain_reseller_id', 'reseller_id'),
        ]);
    }

    /**
     * Scope a query to only include domains for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ad_domain_reseller_id', $resellerId);
        }

        return $query;
    }
}
