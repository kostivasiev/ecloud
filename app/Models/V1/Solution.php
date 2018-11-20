<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\StringProperty;

use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class Solution extends Model implements Filterable, Sortable
{
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
     * The attributes that should be cast to native types
     *
     * @var array
     */
    protected $casts = [
        'ucs_reseller_id' => 'integer',
    ];


    /**
     * Ditto configuration
     * ----------------------
     */


    /**
     * Fudge until ditto supports column aliases
     * @param $key
     * @return string
     */
    public function __get($key)
    {
        return $this->attributes[$this->table . '_' . $key];
    }
    public function __set($key, $value)
    {
        $this->attributes[$this->table . '_' . $key] = $value;
    }


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
     * @param FilterFactory $factory
     * @return array
     */
    public function filterableColumns($factory)
    {
        return [
            $factory->create('id', Filter::$primaryKeyDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('type', Filter::$stringDefaults),
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
            $factory->create('name'),
            $factory->create('type'),
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
     * Maps a UCS Reseller to a UCS Datacentre
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function UCSDatacentre()
    {
        return $this->hasOne(
            'App\Models\V1\UCSDatacentre',
            'ucs_datacentre_id',
            'ucs_reseller_datacentre_id'
        );
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
