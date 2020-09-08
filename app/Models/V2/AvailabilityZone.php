<?php

namespace App\Models\V2;

use App\Services\NsxService;
use App\Traits\V2\CustomKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use UKFast\DB\Ditto\Factories\FilterFactory;
use UKFast\DB\Ditto\Factories\SortFactory;
use UKFast\DB\Ditto\Filter;
use UKFast\DB\Ditto\Filterable;
use UKFast\DB\Ditto\Sortable;

/**
 * Class AvailabilityZones
 * @package App\Models\V2
 * @method static findOrFail(string $zoneId)
 */
class AvailabilityZone extends Model implements Filterable, Sortable
{
    use CustomKey, SoftDeletes;

    public $keyPrefix = 'az';
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'code',
        'name',
        'datacentre_site_id',
        'region_id',
        'is_public',
        'nsx_manager_endpoint',
        'nsx_edge_cluster_id',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'datacentre_site_id' => 'integer',
    ];

    /**
     * @var NsxService
     */
    protected $nsxService;

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function vpns()
    {
        return $this->hasMany(Vpn::class);
    }

    public function networks()
    {
        return $this->hasMany(Network::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function nsxClient() : NsxService
    {
        if (!$this->nsxService) {
            $this->nsxService = app()->makeWith(NsxService::class, [
                'nsx_manager_endpoint' => $this->nsx_manager_endpoint,
                'nsx_edge_cluster_id' => $this->nsx_edge_cluster_id,
            ]);
        }
        return $this->nsxService;
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\FilterFactory $factory
     * @return array|\UKFast\DB\Ditto\Filter[]
     */
    public function filterableColumns(FilterFactory $factory)
    {
        return [
            $factory->create('id', Filter::$stringDefaults),
            $factory->create('code', Filter::$stringDefaults),
            $factory->create('name', Filter::$stringDefaults),
            $factory->create('datacentre_site_id', Filter::$numericDefaults),
            $factory->create('region_id', Filter::$stringDefaults),
            $factory->create('is_public', Filter::$numericDefaults),
            $factory->create('nsx_manager_endpoint', Filter::$stringDefaults),
            $factory->create('nsx_edge_cluster_id', Filter::$stringDefaults),
            $factory->create('created_at', Filter::$dateDefaults),
            $factory->create('updated_at', Filter::$dateDefaults),
        ];
    }

    /**
     * @param \UKFast\DB\Ditto\Factories\SortFactory $factory
     * @return array|\UKFast\DB\Ditto\Sort[]
     * @throws \UKFast\DB\Ditto\Exceptions\InvalidSortException
     */
    public function sortableColumns(SortFactory $factory)
    {
        return [
            $factory->create('id'),
            $factory->create('code'),
            $factory->create('name'),
            $factory->create('datacentre_site_id'),
            $factory->create('region_id'),
            $factory->create('is_public'),
            $factory->create('nsx_manager_endpoint'),
            $factory->create('nsx_edge_cluster_id'),
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
            $factory->create('code', 'asc'),
        ];
    }

    public function databaseNames()
    {
        return [
            'id'         => 'id',
            'code'       => 'code',
            'name'       => 'name',
            'datacentre_site_id'    => 'datacentre_site_id',
            'region_id'    => 'region_id',
            'is_public'    => 'is_public',
            'nsx_manager_endpoint'    => 'nsx_manager_endpoint',
            'nsx_edge_cluster_id'    => 'nsx_edge_cluster_id',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
