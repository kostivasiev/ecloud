<?php

namespace App\Models\V1;

use App\Traits\V1\UUIDHelper;
use Illuminate\Database\Eloquent\Model;
use UKFast\Api\Resource\Property\IdProperty;
use UKFast\Api\Resource\Property\IntProperty;
use UKFast\Api\Resource\Property\StringProperty;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;

class IopsTier extends Model implements Filterable, Sortable
{
    // Table uses UUID's
    use UUIDHelper;

    protected $table = 'ucs_storage_iops_tiers';

    protected $casts = [
        'max_iops' => 'integer'
    ];

    protected $primaryKey = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    /**
     * The attributes included in the model's JSON form.
     * Admin scope / everything
     *
     * @var array
     */
    protected $visible = [
        'uuid',
        'name',
        'max_iops',
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
            'id' => 'uuid',
            'name' => 'name',
            'limit' => 'max_iops'
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
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('limit', Filter::$numericDefaults)
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
            $factory->create('limit')
        ];
    }

    /**
     * Ditto sorting
     * @param SortFactory $sortFactory
     * @return mixed
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
        return ['id', 'limit'];
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
     * @throws \UKFast\Api\Resource\Exceptions\InvalidPropertyException
     */
    public function properties()
    {
        return [
            IdProperty::create('uuid', 'id', null, 'uuid'),
            StringProperty::create('name', 'name'),
            IntProperty::create('max_iops', 'limit')
        ];
    }
}
