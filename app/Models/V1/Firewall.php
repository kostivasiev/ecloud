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

class Firewall extends Model implements Filterable, Sortable
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'servers';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'servers_id';

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
        'servers_id' => 'integer',
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
            'id' => 'servers_id',
            'ip' => 'servers_ip',
            'hostname' => 'servers_hostname',
            'name' => 'servers_friendly_name',
            'solution_id' => 'servers_ecloud_ucs_reseller_id',
            'role' => 'servers_firewall_role',
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
            $factory->create('hostname', Filter::$stringDefaults),
            $factory->create('solution_id', Filter::$stringDefaults),
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
            $factory->create('hostname'),
            $factory->create('solution_id'),
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
     */
    public function properties()
    {
        return [
            IdProperty::create('servers_id', 'id'),
            StringProperty::create('servers_friendly_name', 'name'),
            StringProperty::create('servers_hostname', 'hostname'),
            StringProperty::create('servers_ip', 'ip'),
            StringProperty::create('servers_firewall_role', 'role'),
            IntProperty::create('servers_ecloud_ucs_reseller_id', 'solution_id'),
        ];
    }

    /**
     * Scope a query to only include sites for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('servers_reseller_id', $resellerId);
        }

        return $query;
    }

    /**
     * Scope a query to only include sites for a given solution
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param $solutionId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithSolution($query, $solutionId)
    {
        $solutionId = filter_var($solutionId, FILTER_SANITIZE_NUMBER_INT);

        $query->where('servers_ecloud_ucs_reseller_id', $solutionId)
            ->join('ucs_reseller', 'ucs_reseller_id', '=', 'servers_ecloud_ucs_reseller_id');

        return $query;
    }
}
