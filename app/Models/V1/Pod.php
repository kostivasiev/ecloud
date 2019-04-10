<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\BooleanProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Pod extends Model implements Filterable, Sortable
{
    /**
     * Eloquent configuration
     * ----------------------
     */
    protected $table = 'ucs_datacentre';
    protected $primaryKey = 'ucs_datacentre_id';

    public $timestamps = false;

    protected $casts = [
        'ucs_datacentre_id' => 'integer',
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
            'id' => 'ucs_datacentre_id',
            'name' => 'ucs_datacentre_public_name',
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
            IdProperty::create('ucs_datacentre_id', 'id'),
            StringProperty::create('ucs_datacentre_public_name', 'name'),

            'services' => [
                BooleanProperty::create('ucs_datacentre_public_enabled', 'public', null, 'Yes', 'No'),
                BooleanProperty::create('ucs_datacentre_burst_enabled', 'burst', null, 'Yes', 'No'),
                BooleanProperty::create('ucs_datacentre_oneclick_enabled', 'appliances', null, 'Yes', 'No'),
            ],
        ];

        $request = app('request');
        if (!$request->user->isAdministrator) {
            return $properties;
        }

        // admin only properties
        return array_merge($properties, [
            IntProperty::create('ucs_datacentre_datacentre_id', 'datacentre_id'),
        ]);
    }
}
