<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\ResourceInterface;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;

use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Selectable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Sort;

class Solution extends Model implements Filterable, Sortable, Selectable, ResourceInterface
{
    protected $casts = [
        'ucs_reseller_id' => 'integer',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ucs_reseller';

    /**
     * The primary key associated with the model.
     *
     * @var string
     */
    protected $primaryKey = 'ucs_reseller_id';

    /**
     * Indicates if the model should be timestamped
     *
     * @var bool
     */
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
        return [
            'id' => 'ucs_reseller_id',
            'name' => 'ucs_reseller_solution_name',
            'type'   => 'ucs_reseller_type',
        ];
    }

    /**
     * Ditto filtering configuration
     * @return array
     */
    public function filterableColumns()
    {
        return [
            Filter::create('id', Filter::$primaryKeyDefaults),
            Filter::create('name', Filter::$stringDefaults),
            Filter::create('type', Filter::$stringDefaults),
        ];
    }

    /**
     * Ditto sorting configuration
     * @return array
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns()
    {
        return [
            Sort::create('id', 'asc'),
            Sort::create('name', 'asc'),
            Sort::create('type', 'asc'),
        ];
    }

    /**
     * Ditto sorting
     * @return mixed
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort()
    {
        return Sort::create('id', 'asc');
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
            IdProperty::create('ucs_reseller_id', 'id'),
            StringProperty::create('ucs_reseller_solution_name', 'name'),
            StringProperty::create('ucs_reseller_type', 'type'),
        ];
    }


    /**
     * Scope a query to only include solutions for a given reseller
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $resellerId
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReseller($query, $resellerId)
    {
        $resellerId = filter_var($resellerId, FILTER_SANITIZE_NUMBER_INT);

        if (!empty($resellerId)) {
            $query->where('ucs_reseller_reseller_id', $resellerId);
        }

        return $query;
    }
}
