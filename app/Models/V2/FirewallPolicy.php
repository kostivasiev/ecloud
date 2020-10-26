<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class FirewallPolicy
 * @package App\Models\V2
 * @method static findOrFail(string $firewallPolicyId)
 */
class FirewallPolicy extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes, DefaultName;

    public $keyPrefix = 'pcy';
    public $incrementing = false;
    public $timestamps = true;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'name',
        'sequence',
    ];

    protected $visible = [
        'id',
        'name',
        'sequence',
        'created_at',
        'updated_at',
    ];

    public function rules()
    {
        return $this->hasMany(FirewallRule::class);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$enumDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('sequence', Filter::$stringDefaults),
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
            $factory->create('sequence'),
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
            $factory->create('name', 'asc'),
        ];
    }

    /**
     * @return array|string[]
     */
    public function databaseNames()
    {
        return [
            'id'         => 'id',
            'name'       => 'name',
            'sequence'   => 'sequence',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
