<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\Property\IntProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Vlan extends Model implements Filterable, Sortable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'vlan';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'vlan_id';

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
        'vlan_number' => 'integer',
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
            'name' => 'vlan_public_name',
            'number'   => 'vlan_number',
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
            $factory->create('number', Filter::$numericDefaults),
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
            $factory->create('number'),
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
            $sortFactory->create('number', 'asc'),
        ];
    }


    /**
     * Ditto Selectable persistent Properties
     * @return array
     */
    public function persistentProperties()
    {
        return ['number'];
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
            StringProperty::create('vlan_public_name', 'name'),
            IntProperty::create('vlan_number', 'number'),
        ];
    }


    /**
     * Scope a query to only include vlan's for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('vlan_reseller_id', $resellerId);
        }

        return $query;
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

        if (!empty($solutionId)) {
            $query->where('ucs_reseller_id', $solutionId)
                ->join('vlan_ucs_reseller', 'vlan_ucs_reseller_vlan_id', '=', 'vlan_id')
                ->join('ucs_reseller', 'ucs_reseller_id', '=', 'vlan_ucs_reseller_ucs_reseller_id');
        }

        return $query;
    }
}
