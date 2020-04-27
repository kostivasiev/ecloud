<?php

namespace App\Models\V1;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;
use UKFast\DB\Ditto\Filter;
use Ramsey\Uuid\Uuid;
use App\Traits\V1\UUIDHelper;

class PublicSupport extends Model implements Filterable, Sortable
{
    use SoftDeletes;
    use UUIDHelper;

    /**
     * Eloquent configuration
     * ----------------------
     */
    protected $connection = 'ecloud';
    protected $table = 'public_support';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

//    protected $casts = [
//        'created_at' => 'datetime:' . \DateTime::ATOM,
//    ];

//    protected $dateFormat = \DateTime::ATOM;

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
            'id' => 'id',
            'reseller_id' => 'reseller_id',
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
            $factory->create('reseller_id', Filter::$numericDefaults),
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
            $factory->create('reseller_id'),
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
}
