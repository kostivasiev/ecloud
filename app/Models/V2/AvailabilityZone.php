<?php

namespace App\Models\V2;

use App\Events\V2\AvailabilityZone\Created;
use App\Events\V2\AvailabilityZone\Creating;
use App\Events\V2\AvailabilityZone\Deleted;
use App\Services\V2\ArtisanService;
use App\Services\V2\ConjurerService;
use App\Services\V2\KingpinService;
use App\Services\V2\NsxService;
use App\Traits\V2\CustomKey;
use App\Traits\V2\DeletionRules;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use UKFast\Api\Auth\Consumer;
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
    use CustomKey, SoftDeletes, DeletionRules;

    public $keyPrefix = 'az';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $connection = 'ecloud';
    protected $fillable = [
        'id',
        'code',
        'name',
        'datacentre_site_id',
        'region_id',
        'is_public',
        'san_name',
        'ucs_compute_name',
        'is_active',
    ];

    protected $dispatchesEvents = [
        'creating' => Creating::class,
        'created' => Created::class,
        'deleted' => Deleted::class,
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'datacentre_site_id' => 'integer',
        'is_active' => 'boolean',
    ];

    public $children = [
        'routers',
        'instances',
        'loadBalancers'
    ];

    /**
     * @var NsxService
     */
    protected $nsxService;

    /**
     * @var KingpinService
     */
    protected $kingpinService;

    /**
     * @var ConjurerService
     */
    protected $conjurerService;

    /**
     * @var ArtisanService
     */
    protected $artisanService;

    public function routers()
    {
        return $this->hasMany(Router::class);
    }

    public function routerThroughputs()
    {
        return $this->hasMany(RouterThroughput::class);
    }

    public function dhcps()
    {
        return $this->hasMany(Dhcp::class);
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function credentials()
    {
        return $this->hasMany(Credential::class, 'resource_id', 'id');
    }

    public function instances()
    {
        return $this->hasMany(Instance::class);
    }

    public function loadBalancers()
    {
        return $this->hasMany(LoadBalancer::class);
    }

    public function availabilityZoneCapacities()
    {
        return $this->hasMany(AvailabilityZoneCapacity::class);
    }

    public function images()
    {
        return $this->belongsToMany(Image::class);
    }

    public function vpnProfileGroup()
    {
        return $this->hasMany(VpnProfileGroup::class);
    }

    public function nsxService()
    {
        if (!$this->nsxService) {
            $this->nsxService = app()->makeWith(NsxService::class, [$this]);
        }
        return $this->nsxService;
    }

    public function kingpinService()
    {
        if (!$this->kingpinService) {
            $this->kingpinService = app()->makeWith(KingpinService::class, [$this]);
        }
        return $this->kingpinService;
    }

    public function conjurerService()
    {
        if (!$this->conjurerService) {
            $this->conjurerService = app()->makeWith(ConjurerService::class, [$this]);
        }
        return $this->conjurerService;
    }

    public function artisanService()
    {
        if (!$this->artisanService) {
            $this->artisanService = app()->makeWith(ArtisanService::class, [$this]);
        }
        return $this->artisanService;
    }

    public function products()
    {
        return Product::forAvailabilityZone($this);
    }

    public function hostSpecs()
    {
        return $this->belongsToMany(HostSpec::class);
    }

    /**
     * @param bool $advanced
     * @return string
     * @throws \Exception
     */
    public function getNsxEdgeClusterId(Bool $advanced = false): string
    {
        $searchEdgeClusterResponse = json_decode($this->nsxService()->get(
            'api/v1/search/query?query=resource_type:EdgeCluster' .
            '%20AND%20tags.scope:' . config('defaults.tag.scope') .
            '%20AND%20tags.tag:' . ($advanced ? config('defaults.tag.networking.advanced') : config('defaults.tag.networking.default'))
        )->getBody()->getContents());

        if ($searchEdgeClusterResponse->result_count != 1) {
            throw new \Exception(
                'Failed to determine ' .
                ($advanced ? 'advanced networking' : 'standard') .
                ' edge cluster ID for availability zone ' . $this->id
            );
        }

        return $searchEdgeClusterResponse->results[0]->id;
    }

    /**
     * @param $query
     * @param $user
     * @return mixed
     */
    public function scopeForUser($query, Consumer $user)
    {
        if ($user->isAdmin()) {
            return $query;
        }

        if (in_array($user->resellerId(), config('reseller.internal'))) {
            return $query;
        }

        return $query->whereHas('region', function ($query) {
            $query->where('is_public', '=', true);
        })->where('is_public', '=', true);
    }

    /**
     * @param FilterFactory $factory
     * @return array|Filter[]
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
            $factory->create('san_name', Filter::$stringDefaults),
            $factory->create('ucs_compute_name', Filter::$stringDefaults),
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
            $factory->create('code'),
            $factory->create('name'),
            $factory->create('datacentre_site_id'),
            $factory->create('region_id'),
            $factory->create('is_public'),
            $factory->create('san_name'),
            $factory->create('ucs_compute_name'),
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
            'id' => 'id',
            'code' => 'code',
            'name' => 'name',
            'datacentre_site_id' => 'datacentre_site_id',
            'region_id' => 'region_id',
            'is_public' => 'is_public',
            'san_name' => 'san_name',
            'ucs_compute_name' => 'ucs_compute_name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
    }
}
