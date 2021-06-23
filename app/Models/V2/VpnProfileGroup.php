<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use App\Traits\V2\DeletionRules;
use App\Traits\V2\Syncable;
use App\Traits\V2\Taskable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\Api\Auth\Consumer;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

class VpnProfileGroup extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName, DeletionRules, Syncable, Taskable;

    public $keyPrefix = 'vpnpg';

    public function __construct(array $attributes = [])
    {
        $this->timestamps = true;
        $this->incrementing = false;
        $this->keyType = 'string';
        $this->connection = 'ecloud';
        $this->fillable = [
            'id',
            'name',
            'description',
            'ike_profile_id',
            'ipsec_profile_id',
            'dpd_profile_id',
        ];
        parent::__construct($attributes);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('description', Filter::$stringDefaults),
            $factory->create('ike_profile_id', Filter::$stringDefaults),
            $factory->create('ipsec_profile_id', Filter::$stringDefaults),
            $factory->create('dpd_profile_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('name'),
            $factory->create('description'),
            $factory->create('ike_profile_id'),
            $factory->create('ipsec_profile_id'),
            $factory->create('dpd_profile_id'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('id', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'description' => 'description',
            'ike_profile_id' => 'ike_profile_id',
            'ipsec_profile_id' => 'ipsec_profile_id',
            'dpd_profile_id' => 'dpd_profile_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
