<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;

use App\Scopes\eCloudVmServersScope;

use UKFast\Api\Resource\Property\StringProperty;
use UKFast\Api\Resource\ResourceInterface;
use UKFast\Api\Resource\Property\IdProperty;

use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Selectable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Sort;

class VMModel extends Model implements Filterable, Sortable, Selectable, ResourceInterface
{

    /**
     * Cast our database columns to the correct data type.
     *
     * @var array
     */
    protected $casts = [
        'servers_id' => 'integer',
        'servers_friendly_name' => 'string',
        'servers_cpu' => 'string',
        'servers_memory' => 'integer',
        'servers_hdd' => 'string',
        'servers_platform' => 'string',
        'servers_backup' => 'string',
        'servers_advanced_support' => 'string'
    ];

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
     * The "booting" method of the model.
     * Apply a scope/filter to ** ALL ** Queries using this model of 'servers_type', '=', 'ecloud vm'
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new eCloudVmServersScope);
    }

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
            'name' => 'servers_friendly_name',
            'cpu' => 'servers_cpu',
            'ram' => 'servers_memory',
            'hdd' => 'servers_hdd',
            'platform' => 'servers_platform',
            'backup' => 'servers_backup',
            'support' => 'servers_advanced_support'
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
            Filter::create('cpu', Filter::$stringDefaults),
            Filter::create('ram', Filter::$numericDefaults),
            Filter::create('hdd', Filter::$stringDefaults),
            Filter::create('platform', Filter::$stringDefaults),
            Filter::create('backup', Filter::$stringDefaults),
            Filter::create('support', Filter::$stringDefaults),
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
            Sort::create('cpu', 'asc'),
            Sort::create('ram', 'asc'),
            Sort::create('hdd', 'asc'),
            Sort::create('platform', 'asc'),
            Sort::create('backup', 'asc'),
            Sort::create('support', 'asc'),

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
     * End Ditto configuration
     * ----------------------
     */

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
            StringProperty::create('servers_cpu', 'cpu'),
            StringProperty::create('servers_ram', 'ram'),
            StringProperty::create('servers_hdd', 'hdd'),
            StringProperty::create('servers_platform', 'platform'),
            StringProperty::create('servers_backup', 'backup'),
            StringProperty::create('support', 'support'),
        ];
    }



    /**
     * Scope a query to only include firewalls for a given reseller
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

}
