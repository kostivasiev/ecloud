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

class SolutionSite extends Model implements Filterable, Sortable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ucs_site';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'ucs_site_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast to native types
     *
     * @var array
     */
    protected $casts = [
        'ucs_site_id' => 'integer',
        'ucs_site_order' => 'integer',
        'ucs_site_reseller_id' => 'integer',
        'ucs_site_datacentre_id' => 'integer',
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
            'id'            => 'ucs_site_id',
            'state'         => 'ucs_site_state',
            'order'         => 'ucs_site_order',
            'reseller_id'   => 'ucs_site_reseller_id',
            'datacentre_id' => 'ucs_site_datacentre_id',
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
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('state', Filter::$stringDefaults),
            $factory->create('order', Filter::$numericDefaults),
            $factory->create('reseller_id', Filter::$numericDefaults),
            $factory->create('datacentre_id', Filter::$numericDefaults),
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
            $factory->create('state'),
            $factory->create('order'),
            $factory->create('reseller_id'),
            $factory->create('datacentre_id'),
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
            $sortFactory->create('order', 'asc'),
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
            IdProperty::create('ucs_site_id', 'id'),
            StringProperty::create('ucs_site_state', 'state'),
            IntProperty::create('ucs_site_order', 'order'),
            IntProperty::create('ucs_site_reseller_id', 'reseller_id'),
            IntProperty::create('ucs_site_datacentre_id', 'datacentre_id'),
        ];
    }

    /**
     * Scope a query to only include vlan's for a given solution
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $solutionId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('ucs_site_ucs_reseller_id', $solutionId)
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'ucs_site_ucs_reseller_id');

        return $query;
    }
}
