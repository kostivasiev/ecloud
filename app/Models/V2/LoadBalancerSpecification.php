<?php

namespace App\Models\V2;

use App\Traits\V2\CustomKey;
use App\Traits\V2\DefaultName;
use Database\Factories\V2\LoadBalancerSpecificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class AvailabilityZoneCapacity
 * @package App\Models\V2
 * @method static find(string $routerId)
 * @method static findOrFail(string $routerUuid)
 */
class LoadBalancerSpecification extends Model implements Filterable, Sortable
{
    use CustomKey, HasFactory, DefaultName;

    public $keyPrefix = 'lbs';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'name',
        'node_count',
        'cpu',
        'ram',
        'hdd',
        'iops',
        'image_id'
    ];

    protected $casts = [
        'id' => 'string',
        'name' => 'string',
        'node_count' => 'integer',
        'cpu' => 'integer',
        'ram' => 'integer',
        'hdd' => 'integer',
        'iops' => 'integer',
        'image_id' => 'string',
    ];

    public function lbc()
    {
        return $this->hasMany(LoadBalancerCluster::class, 'load_balancer_spec_id', 'id');
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
            $factory->create('node_count', Filter::$numericDefaults),
            $factory->create('cpu', Filter::$numericDefaults),
            $factory->create('ram', Filter::$numericDefaults),
            $factory->create('hdd', Filter::$numericDefaults),
            $factory->create('iops', Filter::$numericDefaults),
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
            $factory->create('node_count'),
            $factory->create('cpu'),
            $factory->create('ram'),
            $factory->create('hdd'),
            $factory->create('iops'),
            $factory->create('created_at'),
            $factory->create('updated_at'),
        ];
    }

    /**
     * @param SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort|\UKFast\DB\Ditto\Sort[]|null
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function defaultSort(SortFactory $factory)
    {
        return [
            $factory->create('created_at', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'node_count' => 'node_count',
            'cpu' => 'cpu',
            'ram' => 'ram',
            'hdd' => 'hdd',
            'iops' => 'iops',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
